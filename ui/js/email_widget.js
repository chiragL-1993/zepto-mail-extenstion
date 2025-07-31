var entityId, entityName, entityURLName, orgId, zgId, timezone, loginUserId, emailAddress, buttonPosition, emailFieldMapping, optoutFieldMapping;
var bulkEmail = true;
var viewEmail = false;
var countryCode = DEFAULT_COUNTRY_CODE;
let emailBodyEditor;
var recordsReadyForEmail = {};
var errorArray = {
    otherErrors: [],
    optedOut: [],
    emptyEmail: [],
    invalidEmail: []
};

const ERROR_TEXT = {
    recordRetrieve: 'Error retrieving record data, try again!',
    fieldNotPresent: 'Invalid field ${fieldMapping}, please check settings to configure correct field for Email or Email Opt Out field mapping',
    optedOut: 'Recipient(s) opted out of the Email',
    emptyEmail: 'Recipient(s) without a Email address',
    invalidEmail: 'Recipient(s) without a valid Email address',
    emailLength: 'Message length exceeds the max character limit allowed by Burst, please shorten it',
    invalidMergeFields: 'Invalid merge fields found, please check and remove the invalid merge fields'
}

$(document).ready(async function () {
    $(function () {
        ClassicEditor
            .create(document.querySelector('#email-body'))
            .then(editor => {
                emailBodyEditor = editor;
            })
            .catch(error => {
                console.error(error);
            });
    });
    $('div.ck-powered-by-balloon').remove();
    // Subscribe to the EmbeddedApp onPageLoad event before initializing 
    ZOHO.embeddedApp.on('PageLoad', async function (data) {
        showLoader();
        $(".is-view-email").hide();

        // Initialize Switchery with a small size
        var sendLaterSwitch = new Switchery(document.getElementById('send-later-toggle'), { size: 'small', color: '#54A4DA' });
        var addOptOutSwitch = new Switchery(document.getElementById('add-optout'), { size: 'small', color: '#54A4DA' });

        $('#add-optout-tooltip').hover(function () {
            $('#tooltip-default').toggleClass('opacity-0 invisible');
        });

        // Initialize date picker
        flatpickr('.datetime-picker', {
            enableTime: true,
            dateFormat: "d/m/Y h:i K",
            minDate: "today",
        });

        // Get the Entity Name and Entity ID from which the widget is initialized (DetailView)
        entityName = data.Entity;
        entityId = data.EntityId;
        buttonPosition = data.ButtonPosition;
        if (SINGLE_RECORD_ACTION_BUTTONS.includes(buttonPosition)) {
            bulkEmail = false;
            entityId = data.EntityId[0];
        } else if (buttonPosition == "ListViewWithoutRecord") {
            viewEmail = true;
            bulkEmail = false;
            $(".is-view-email").show();

            populateViews(entityName);
        }

        // Assign Module URL name
        assignModuleURLName();

        // Get Current User info and push to hidden field
        var userData = await ZOHO.CRM.CONFIG.getCurrentUser();
        loginUserId = userData.users[0].id;

        if (bulkEmail || viewEmail) {
            $('#campaign-container').toggle(true);
        }

        // Get OrgInfo
        var orgData = await ZOHO.CRM.CONFIG.getOrgInfo();
        orgId = orgData.org[0].id;
        zgId = orgData.org[0].zgid;
        timezone = orgData.org[0].time_zone;
        $('#timezone').text(timezone);

        // Get saved Email Settings
        var savedData = await getSavedSettings(orgId);
        if (savedData.success && isSettingsPresent(savedData, entityName)) { // Check if all the configurations are present
            // Assign field mappings
            emailFieldMapping = savedData.data.module_field_mappings['email_field_mapping_' + entityName];
            optoutFieldMapping = savedData.data.module_field_mappings['email_optout_field_mapping_' + entityName];

            if (savedData.data.zepto_mail_sender_id) {
                if (savedData.data.zepto_mail_sender_id.length === 1) {
                    $('#sender-id').val('');
                } else {
                    //We have multiple sender IDs, so display a dropdown
                    $('#sender-id').val(savedData.data.zepto_mail_sender_id);
                }
            }
            // Populate Module Merge Fields
            await populateModuleField(entityName, 'module-merge-fields');
            // Populate User Merge Fields
            await populateModuleField(USER_MODULE, 'user-merge-fields');
            // Populate Templates for the module
            await populateTemplates(entityName);

            copyFromLocalStorage(entityName, sendLaterSwitch, addOptOutSwitch);
        } else {
            // If Email Settings not present, show error
            $('#form-container').hide();
            $('#error-container').show();
            $('#settings-error-message').show();
        }

        // Add event listener for "Send it later" toggle switch
        $('#send-later-toggle').change(function () {
            $('#schedule-container').toggle(this.checked);
        });

        // Add event listener for Next button
        $('#next-button').on('click', function () {
            if (viewEmail) {
                if (!$('#savedViews').val()) {
                    isValid = false;
                    $('#savedViews').addClass('invalid');
                    // Scroll to the first invalid field (optional)
                    $('html, body').animate({
                        scrollTop: $('.invalid').first().offset().top - 20
                    }, 500);
                    return;
                }
                if (!confirm("You are sending to a CRM view. Calculating the number of recipients may take some time. Are you sure you want to proceed?")) {
                    return;
                };
            }
            showPreviewPage();
            pushToLocalStorage(entityName);
        });

        // Add event listener for Back button on the preview page
        $('#back-button').on('click', function () {
            showFormPage();
        });

        // Form submission 
        $('#send-button').on('click', function () {
            if (bulkEmail || viewEmail) {
                submitFormForBulkEmail();
            } else {
                submitFormForSingleEmail();
            }
        });

        // Add event listener to close the widget
        $('#close-button').on('click', function () {
            closeWidget();
        });

        // Insert selected option into the textarea
        $('#module-merge-fields').on('change', function () {
            insertMergeField($(this), entityName);
        });
        $('#user-merge-fields').on('change', function () {
            insertMergeField($(this), USER_MODULE);
        });

        // Add event listener to copy selected template content
        $('#message-template').on('change', function () {
            var $templateSelect = $(this);
            var selectedValue = $templateSelect.val();
            var previousValue = $('#message-template').data('prevTemplate');
            //var emailBody = $('#email-body').val();
            var emailBody = emailBodyEditor.getData();;
            let emailBodyTextOnly = $('<div>').html(emailBody).text().replace(/\${.*?}/g, '');
            if (emailBodyTextOnly && emailBodyTextOnly.trim()) {
                var confirmationMessage = "This action will overwrite the Email Message. Are you sure you want to proceed?";
                showConfirmationDialog(confirmationMessage, function (proceed) {
                    if (proceed) {
                        // Save the current template for future reference
                        $('#message-template').data('prevTemplate', selectedValue);
                        //$('#email-body').val('');
                        emailBodyEditor.setData('');// Clear Email Template Body
                        insertTemplateText($templateSelect);
                    } else {
                        // Reset the selected option if the user cancels
                        $templateSelect.val(previousValue);
                        pushToLocalStorage(entityName); //update cache as well
                    }
                });
            } else {
                insertTemplateText($templateSelect);
            }
        });

        // Add event listener to display settings widget
        $('#open-settings').on('click', function () {
            openSettingsWebtab();
        });

        /** Push to browser cache **/
        // Bind blur event for text input field
        $('#campaign-name').on('blur', function () {
            pushToLocalStorage(entityName);
        });

        // Bind blur event for select field
        $('#message-template').on('change', function () {
            pushToLocalStorage(entityName);
        });

        // Bind change event for checkbox field
        $('#send-later-toggle').on('change', function () {
            pushToLocalStorage(entityName);
        });

        // Bind blur event for text input field
        $('#schedule-date').on('change', function () {
            pushToLocalStorage(entityName);
        });

        // Bind blur event for select field
        $('#sender-id').on('blur', function () {
            pushToLocalStorage(entityName);
        });

        // Bind blur event for textarea field
        $('#email-body').on('blur', function () {
            pushToLocalStorage(entityName);
        });

        // Bind change event for checkbox field
        $('#add-optout').on('change', function () {
            pushToLocalStorage(entityName);
        });

        hideLoader();
    });

    // Initializing the widget.
    ZOHO.embeddedApp.init();

});



async function getSavedViews(entity) {
    let savedFilters = await ZOHO.CRM.META.getCustomViews({ "Entity": entity });
    let system_views = [];
    let custom_views = [];

    for (let i in savedFilters.custom_views) {
        if (savedFilters.custom_views[i].system_defined) {
            system_views.push(savedFilters.custom_views[i]);
        } else {
            custom_views.push(savedFilters.custom_views[i]);
        }
    }
    return {
        system_views: system_views,
        custom_views: custom_views
    };
}
async function populateViews(entityType) {
    console.log("Getting filters for module:", entityType);
    let savedFilters = await getSavedViews(entityType);
    console.log("Saved Filters:", savedFilters);

    //Push those filters to the dropdown
    let dropdown = $('#savedViews');
    dropdown.empty();
    dropdown.append('<option value="">Select a Saved View</option>');
    if (savedFilters.custom_views.length > 0) {
        for (let i in savedFilters.custom_views) {
            dropdown.append('<option value="' + savedFilters.custom_views[i].id + '">' + savedFilters.custom_views[i].name + '</option>');
        }
        dropdown.append('<option value="">------------</option>');
    }
    if (savedFilters.system_views.length > 0) {
        for (let i in savedFilters.system_views) {
            dropdown.append('<option value="' + savedFilters.system_views[i].id + '">' + savedFilters.system_views[i].name + '</option>');
        }
    }

    if (savedFilters.custom_views.length < 1 && savedFilters.system_views.length < 1) {
        $('#savedViews').delete();
        //Hide the form and display an error
        $("#form-container").hide();
        $("#error-container").show();
        $("#settings-error-message").text("This module does not have any saved views. Please create a saved view to use this feature.");
        $("#open-settings").text("Close");
    }
}

async function populateTemplates(entityName) {
    var query = "((" + EMAIL_TEMPLATE_FIELDS[2] + ":equals:" + entityName + ")and(" + EMAIL_TEMPLATE_FIELDS[6] + ":equals:Active))";
    var templateResponse = await ZOHO.CRM.API.searchRecord({ Entity: EMAIL_TEMPLATE_MODULE, Type: "criteria", Query: query })
    if (templateResponse && templateResponse.data && templateResponse.data.length > 0) {
        $.each(templateResponse.data, function (index, template) {
            $('#message-template').append($('<option>', {
                value: template[EMAIL_TEMPLATE_FIELDS[0]],
                text: template[EMAIL_TEMPLATE_FIELDS[1]],
                'data-template-text': template[EMAIL_TEMPLATE_FIELDS[3]]
            }));
        });
    }
}

async function populateModuleField(module, elementId) {
    // Remove all options except the default one
    $('#' + elementId + ' optgroup').remove();

    if (module) {
        // Fetch Fields metadata of the User
        var moduleLayouts = await ZOHO.CRM.META.getLayouts({ 'Entity': module });

        // Create data object to fill the picklist
        var moduleMergeFieldsData = {};
        $.each(moduleLayouts.layouts[0].sections, function (index, section) {
            var sectionName = section.display_label;
            moduleMergeFieldsData[sectionName] = [];
            $.each(section.fields, function (index, field) {
                if (MERGE_FIELD_TYPES_TO_EXCLUDE.includes(field.data_type)) {
                    return true;
                }
                var fieldData = {
                    value: field.api_name,
                    text: field.field_label
                }
                moduleMergeFieldsData[sectionName].push(fieldData);
            });
        });

        // Populate Module Merge Fields picklist
        $.each(moduleMergeFieldsData, function (group, fields) {
            if (fields.length !== 0) {
                $('#' + elementId).append($('<optgroup>', {
                    label: group
                }));

                // Add Record ID merge field in the System Information section
                if (group == "System Information") {
                    $('#' + elementId + ' optgroup[label="' + group + '"]').append($('<option>', {
                        value: "EntityId",
                        text: "Record ID"
                    }));
                }

                $.each(fields, function (index, field) {
                    $('#' + elementId + ' optgroup[label="' + group + '"]').append($('<option>', {
                        value: field.value,
                        text: field.text
                    }));
                });
            }
        });
    }
}

/*function insertMergeField($mergeField, selectedModule) {
    var cursorPosition = $('#email-body').prop('selectionStart');
    var selectedOption = $mergeField.val();
    selectedOption = '${' + selectedModule + '.' + selectedOption + '}';

    if (cursorPosition === undefined) {
        // If cursor position is undefined, append the selected option at the end
        $('#email-body').val($('#email-body').val() + selectedOption);
    } else {
        // Insert the selected option at the cursor position
        var currentValue = $('#email-body').val();
        $('#email-body').val(currentValue.substring(0, cursorPosition) + selectedOption + currentValue.substring(cursorPosition));
    }

    // Push to Cache
    pushToLocalStorage(entityName);
    // Reset the select to the default option after inserting the value
    $mergeField.val('');
}*/
function insertMergeField($mergeField, selectedModule) {
    if (!emailBodyEditor) return;

    const selectedOption = $mergeField.val();
    const mergeTag = '${' + selectedModule + '.' + selectedOption + '}';

    const viewFragment = emailBodyEditor.data.processor.toView(mergeTag);
    const modelFragment = emailBodyEditor.data.toModel(viewFragment);

    emailBodyEditor.model.change(writer => {
        const selection = emailBodyEditor.model.document.selection;
        writer.insert(modelFragment, selection.getFirstPosition());
    });
    // Push to Cache
    pushToLocalStorage(entityName);

    $mergeField.val(''); // Reset the dropdown
}

function insertTemplateText($selectedTemplate) {
    var selectedOption = $selectedTemplate.find('option:selected');
    var templateText = selectedOption.data('template-text');
    //$('#email-body').val(templateText);
    emailBodyEditor.setData(templateText);
    // Push to Cache
    pushToLocalStorage(entityName);
}

// Function to show the form page
function showFormPage() {
    // Clear values in preview
    $('#campaign-value').text('');
    $('#template-value').text('');
    $('#message-value').text('');
    $('#schedule-value').text('');
    clearNotices();

    // Clear all the error and record arrays
    emailAddress = null;
    recordsReadyForEmail = {};
    errorArray.emptyEmail = [];
    errorArray.invalidEmail = [];
    errorArray.optedOut = [];
    errorArray.otherErrors = [];

    // Show form, hide preview
    $('#form-container').show();
    $('#preview-container').hide();
}

// Function to show the preview page
async function showPreviewPage() {
    if (validateForm()) {
        // Copy values from form to preview
        showLoader();

        if (bulkEmail) {
            await validateBulkRecord();
            await processBulkRecordErrors();
        } else if (viewEmail) {
            await validateViewRecord();
            return;
        } else {
            $('.campaign-section').hide();
            await validateSingleRecord();
            await processSingleRecordErrors();
        }

        showConfirm();
    }
}

function showConfirm() {
    //let emailBody = $('#email-body').val();
    let emailBody = emailBodyEditor.getData();
    if ($('#add-optout').is(':checked')) {
        emailBody += OPTOUT_TEXT;
    }
    const creditsRequired = calculateEmailCredits(emailBody);
    let recordsReadyForEmailKeysArray = Object.keys(recordsReadyForEmail);
    $('#credit-value').text(creditsRequired * recordsReadyForEmailKeysArray.length);
    $('#recipient-value').text(bulkEmail || viewEmail ? entityId.length : 1);


    $('#campaign-value').text($('#campaign-name').val() ? $('#campaign-name').val() : ' - ');
    $('#template-value').text($('#message-template  option:selected').val() ? $('#message-template  option:selected').text() : ' - ');
    $('#message-value').html(nl2br(emailBody));
    $('#schedule-value').text($('#send-later-toggle').is(':checked') ? $('#schedule-date').val() : 'Immediate');

    // Hide form, show preview
    $('#form-container').hide();
    $('#preview-container').show();
    hideLoader();
}

// Validation function
function validateForm() {
    var isValid = true;

    // Reset previous validation styles
    $('input, select, textarea').removeClass('invalid');
    $('#email-error').hide();

    if ($('#send-later-toggle').is(':checked')) {
        if (!$('#schedule-date').val()) {
            isValid = false;
            $('#schedule-date').addClass('invalid');
        }
    }

    /*if (!$('#email-body').val().trim()) {
        isValid = false;
        $('#email-body').addClass('invalid');
    } else {
        let emailBody = $('#email-body').val();
        if ($('#add-optout').is(':checked')) {
            emailBody += OPTOUT_TEXT;
        }
        const creditsRequired = calculateEmailCredits(emailBody);
        const isValidMergeFields = validateMergeFields(emailBody, USER_MODULE, entityName);
        if (creditsRequired > 4) {
            isValid = false;
            $('#email-body').addClass('invalid');
            $('#email-error').show();
            $('#email-error-text').text(ERROR_TEXT.emailLength);
        } else if (!isValidMergeFields) {
            isValid = false;
            $('#email-body').addClass('invalid');
            $('#email-error').show();
            $('#email-error-text').text(ERROR_TEXT.invalidMergeFields);
        }
    }*/

    if (emailBodyEditor) {
        let emailBody = emailBodyEditor.getData().trim(); // Get CKEditor content as HTML

        if (!emailBody) {
            isValid = false;
            $('#email-body').addClass('invalid');
        } else {
            if ($('#add-optout').is(':checked')) {
                emailBody += OPTOUT_TEXT;
            }

            const creditsRequired = calculateEmailCredits(emailBody);
            const isValidMergeFields = validateMergeFields(emailBody, USER_MODULE, entityName);

            if (creditsRequired > 4) {
                isValid = false;
                $('#email-body').addClass('invalid');
                $('#email-error').show();
                $('#email-error-text').text(ERROR_TEXT.emailLength);
            } else if (!isValidMergeFields) {
                isValid = false;
                $('#email-body').addClass('invalid');
                $('#email-error').show();
                $('#email-error-text').text(ERROR_TEXT.invalidMergeFields);
            } else {
                $('#email-error').hide(); // Clear error on valid input
                $('#email-body').removeClass('invalid'); // Optional: clear invalid class
            }
        }
    }


    if (!$('#sender-id').val().trim()) {
        isValid = false;
        $('#sender-id').addClass('invalid');
    }

    if (viewEmail && !$('#savedViews').val()) {
        isValid = false;
        $('#savedViews').addClass('invalid');
    }

    if (!isValid) {
        // Scroll to the first invalid field (optional)
        $('html, body').animate({
            scrollTop: $('.invalid').first().offset().top - 20
        }, 500);
    }

    return isValid;
}

// Validate Single Record to check optout, and valid mobile number 
async function validateSingleRecord() {
    let entityData = await ZOHO.CRM.API.getRecord({ Entity: entityName, RecordID: entityId });

    if (!entityData.data) {
        errorArray.otherErrors.push({ text: ERROR_TEXT.recordRetrieve, count: 1 });
    }
    if (!(emailFieldMapping in entityData.data[0])) {
        errorText = ERROR_TEXT.fieldNotPresent.replace(/\${fieldMapping}/g, emailFieldMapping);
        errorArray.otherErrors.push({ text: errorText, count: 1 });
    }
    if (!(optoutFieldMapping in entityData.data[0])) {
        errorText = ERROR_TEXT.fieldNotPresent.replace(/\${fieldMapping}/g, optoutFieldMapping);
        errorArray.otherErrors.push({ text: errorText, count: 1 });
    }
    if (entityData.data[0][optoutFieldMapping]) {
        errorArray.optedOut.push({ text: ERROR_TEXT.optedOut, count: 1 });
    }

    if (!entityData.data[0][optoutFieldMapping]) {
        const email = entityData.data[0][emailFieldMapping];
        if (!email || email.trim() === "") {
            errorArray.emptyEmail.push({ text: ERROR_TEXT.emptyEmail, count: 1 });
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errorArray.invalidEmail.push({ text: ERROR_TEXT.invalidEmail, count: 1 });
        } else {
            emailAddress = email;
            recordsReadyForEmail[entityId] = email;
        }
        /*let parsedNumber = await validatePhoneNumber(entityData.data[0][emailFieldMapping], countryCode.toUpperCase());
        if (parsedNumber.isEmpty) {
            errorArray.emptyEmail.push({ text: ERROR_TEXT.emptyEmail, count: 1 });
        }
        if (!parsedNumber.isEmpty && !parsedNumber.isValid) {
            errorArray.invalidEmail.push({ text: ERROR_TEXT.invalidEmail, count: 1 });
        }
        if (!parsedNumber.isEmpty && parsedNumber.isValid) {
            formattedNumber = parsedNumber.formattedNumber;
            recordsReadyForEmail[entityId] = parsedNumber.formattedNumber;
        }*/
    }
}

// Validate Bulk Records to check how many have optedout, and how many have invalid or empty mobile numbers
async function validateBulkRecord() {
    console.log("Validating Bulk Records");
    console.log("Entity IDs:", entityId);
    await processArrayInChunks(entityId, 50, getEntitiesData);
}

// Validate View Records to check how many have optedout, and how many have invalid or empty mobile numbers
async function validateViewRecord() {
    let viewId = $('#savedViews').val();
    let formData = [
        {
            name: "view_id",
            value: viewId
        },
        {
            name: "action",
            value: "get_view_records"
        }
    ];
    formData.push({ name: 'zgid', value: zgId });
    formData.push({ name: 'timezone', value: timezone });
    formData.push({ name: 'login_userid', value: loginUserId });
    formData.push({ name: 'module_name', value: entityName });
    formData.push({ name: 'module_data', value: "na" });
    formData.push({ name: 'email_body', value: "na" });
    formData.push({ name: 'module_url_name', value: entityURLName });
    formData.push({ name: 'country', value: countryCode });
    $.ajax({
        url: SQUIRREL_EXTENSION_PATH + SEND_BULK_EMAIL,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: async function (response) {
            console.log("Response", response);
            if (response.success) {
                entityId = response.message.records;
                await validateBulkRecord();
                await processBulkRecordErrors();
                showConfirm();

            }

        }
    });
}

async function getEntitiesData(chunk) {
    return new Promise(async (resolve, reject) => {
        let selectQuery = `Select id, ${emailFieldMapping}, ${optoutFieldMapping} from ${entityName} WHERE id in`;
        selectQuery += "(" + chunk.join(",") + ")";
        let config = {
            "select_query": selectQuery
        }
        try {
            coqlResponse = await ZOHO.CRM.API.coql(config);
            if (coqlResponse && coqlResponse.data && coqlResponse.data.length > 0) {
                $.each(coqlResponse.data, async function (index, entity) {
                    if (entity[optoutFieldMapping]) {
                        errorArray.optedOut.push({ ...entity });
                    }

                    if (!entity[optoutFieldMapping]) {
                        const email = entity[emailFieldMapping];
                        if (!email || email.trim() === "") {
                            errorArray.emptyEmail.push({ text: ERROR_TEXT.emptyEmail, count: 1 });
                        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            errorArray.invalidEmail.push({ text: ERROR_TEXT.invalidEmail, count: 1 });
                        } else {
                            emailAddress = email;
                            recordsReadyForEmail[entityId] = email;
                        }
                        if (email.isEmpty) {
                            errorArray.emptyEmail.push({ ...entity });
                        }
                        if (!email.isEmpty && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            errorArray.invalidEmail.push({ ...entity });
                        }
                        if (!email.isEmpty && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            emailAddress = email;
                            recordsReadyForEmail[entity.id] = email;
                        }
                        /*let parsedNumber = await validatePhoneNumber(entity[emailFieldMapping], countryCode.toUpperCase());
                        if (parsedNumber.isEmpty) {
                            errorArray.emptyEmail.push({ ...entity });
                        }
                        if (!parsedNumber.isEmpty && !parsedNumber.isValid) {
                            errorArray.invalidEmail.push({ ...entity });
                        }
                        if (!parsedNumber.isEmpty && parsedNumber.isValid) {
                            recordsReadyForEmail[entity.id] = parsedNumber.formattedNumber;
                        }*/
                    }
                });
            }
            resolve();
        } catch (error) {
            if (error.code && error.code == 'INVALID_QUERY' && error.details && error.details.column_name) {
                let errorText = ERROR_TEXT.fieldNotPresent.replace(/\${fieldMapping}/g, error.details.column_name);
                errorArray.otherErrors.push({ text: errorText, count: '' });
                reject();
            } else {
                let errorText = error.message + " - " + JSON.stringify(error.details);
                errorArray.otherErrors.push({ text: errorText, count: '' });
                reject();
            }
        }
    });
}

async function processSingleRecordErrors() {
    if (errorArray.otherErrors.length > 0 || errorArray.optedOut.length > 0 || errorArray.emptyEmail.length > 0 || errorArray.invalidEmail.length > 0) {
        for (const key in errorArray) {
            $.each(errorArray[key], function (index, error) {
                addNotice(error.text, error.count);
            });
        }
        // Disable send button
        $('#send-button').prop('disabled', true).removeClass('bg-[#54a4da]').removeClass('hover:bg-sky-500').addClass('bg-gray-400');
    }
}

async function processBulkRecordErrors() {
    if (errorArray.otherErrors.length > 0 || errorArray.optedOut.length > 0 || errorArray.emptyEmail.length > 0 || errorArray.invalidEmail.length > 0) {
        for (const key in errorArray) {
            if (key == 'otherErrors') {
                $.each(errorArray[key], function (index, error) {
                    addNotice(error.text, 1);
                });
            } else {
                if (errorArray[key].length > 0) {
                    addNotice(ERROR_TEXT[key], errorArray[key].length);
                }
            }
        }
        // Disable send button if there is no record ready for Email
        let recordsReadyForEmailKeysArray = Object.keys(recordsReadyForEmail);
        if (recordsReadyForEmailKeysArray.length == 0) {
            $('#send-button').prop('disabled', true).removeClass('bg-[#54a4da]').removeClass('hover:bg-sky-500').addClass('bg-gray-400');
        }
    }
}

// Handle form submission for single email
async function submitFormForSingleEmail() {
    // Create an array to store form data
    if ($('#email-owner').val() == "Record Owner") {
        loginUserId = "";
    }
    var formData = [];
    formData.push({ name: 'zgid', value: zgId });
    formData.push({ name: 'timezone', value: timezone });
    formData.push({ name: 'login_userid', value: loginUserId });
    formData.push({ name: 'module_name', value: entityName });
    formData.push({ name: 'module_url_name', value: entityURLName });
    formData.push({ name: 'module_id', value: entityId });
    formData.push({ name: 'email_address', value: emailAddress });
    formData.push({ name: 'campaign_name', value: '' });
    formData.push({ name: 'email_template_id', value: $('#message-template').val() });
    formData.push({ name: 'schedule_at', value: $('#schedule-date').val() });
    formData.push({ name: 'country', value: countryCode });
    formData.push({ name: 'sender_id', value: $('#sender-id').val() });
    //let emailBody = $('#email-body').val()
    let emailBody = emailBodyEditor.getData();
    if ($('#add-optout').is(':checked')) {
        emailBody += OPTOUT_TEXT;
    }
    formData.push({ name: 'email_body', value: emailBody });

    $.ajax({
        url: SQUIRREL_EXTENSION_PATH + SEND_SINGLE_EMAIL,
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            // Show full-page loader before sending the request
            showLoader();
        },
        success: function (response) {
            // Process the response
            if (response.success) {
                // Show success message
                $('#send-button').hide();
                $('#back-button').addClass('invisible')
                $('#close-button').show();
                clearNotices();
                addNotice('', response.message.toUpperCase(), true);
            } else {
                // Show error message
                clearNotices();
                addNotice('', response.message);
            }
            // remove cache
            removeFromLocalStorage(entityName);
        },
        error: function () {
            // Show error message in case of AJAX failure
            clearNotices();
            addNotice('', 'Error sending Email. Please try again');
        },
        complete: function () {
            // Hide full-page loader after request completion
            hideLoader();
        }
    });

}

// Handle form submission for bulk email
async function submitFormForBulkEmail() {
    // Create an array to store form data
    if ($('#email-owner').val() == "Record Owner") {
        loginUserId = "";
    }
    var formData = [];
    formData.push({ name: 'zgid', value: zgId });
    formData.push({ name: 'timezone', value: timezone });
    formData.push({ name: 'login_userid', value: loginUserId });
    formData.push({ name: 'module_name', value: entityName });
    formData.push({ name: 'module_url_name', value: entityURLName });
    formData.push({ name: 'campaign_name', value: $('#campaign-name').val() });
    formData.push({ name: 'email_template_id', value: $('#message-template').val() });
    formData.push({ name: 'schedule_at', value: $('#schedule-date').val() });
    formData.push({ name: 'country', value: countryCode });
    formData.push({ name: 'sender_id', value: $('#sender-id').val() });
    //let emailBody = $('#email-body').val()
    let emailBody = emailBodyEditor.getData();
    if ($('#add-optout').is(':checked')) {
        emailBody += OPTOUT_TEXT;
    }
    formData.push({ name: 'email_body', value: emailBody });
    formData.push({ name: 'module_data', value: JSON.stringify(recordsReadyForEmail) });

    $.ajax({
        url: SQUIRREL_EXTENSION_PATH + SEND_BULK_EMAIL,
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            // Show full-page loader before sending the request
            showLoader();
        },
        success: function (response) {
            // Process the response
            if (response.success) {
                // Show success message
                $('#send-button').hide();
                $('#back-button').addClass('invisible')
                $('#close-button').show();
                clearNotices();
                addNotice('', response.message.toUpperCase(), true);
            } else {
                // Show error message
                clearNotices();
                addNotice('', response.message);
            }
            // remove cache
            removeFromLocalStorage(entityName);
        },
        error: function () {
            // Show error message in case of AJAX failure
            clearNotices();
            addNotice('', 'Error sending Email. Please try again');
        },
        complete: function () {
            // Hide full-page loader after request completion
            hideLoader();
        }
    });

}

async function assignModuleURLName() {
    var moduleData = await ZOHO.CRM.META.getModules();
    $.each(moduleData.modules, function (index, module) {
        if (module.api_name == entityName) {
            entityURLName = module.module_name;
        }
    });
}

function addNotice(message, count, success = false) {
    let newNotice = $('#notices .notice-template')
        .clone()
        .removeClass('notice-template')
        .addClass('notice')
        .show();

    newNotice.find('.notice-message').text(message);
    newNotice.find('.notice-count').text(count);

    $('#notices .notices-group').append(newNotice);
    $('#notices').show();

    if (success) {
        $('.notice-count').removeClass('text-red-700').removeClass('ring-red-600/50').addClass('text-green-700').addClass('ring-green-600/50');
    }
}

function clearNotices() {
    $('#notices').hide();
    $('#notices .notices-group .notice').remove();
}

function pushToLocalStorage(key) {
    let campaignName = $('#campaign-name').val();
    let messageTemplate = $('#message-template').val();
    let sendLater = $('#send-later-toggle').prop('checked');
    let scheduledDate = $('#schedule-date').val();
    let senderNumber = $('#sender-id').val();
    //let emailBody = $('#email-body').val();
    let emailBody = emailBodyEditor.getData();
    let allowOptout = $('#add-optout').prop('checked');

    let cacheData = { campaignName, messageTemplate, sendLater, scheduledDate, senderNumber, emailBody, allowOptout };
    saveToLocalStorage(key, cacheData);
}

function copyFromLocalStorage(key, sendLaterSwitch, addOptOutSwitch) {
    let cachedData = getFromLocalStorage(key);
    if (cachedData) {
        $('#campaign-name').val(cachedData.campaignName);
        cachedData.messageTemplate ? $('#message-template').val(cachedData.messageTemplate) : false;
        $('#schedule-date').val(cachedData.scheduledDate);
        if ($('#sender-id').find(`option[value="${cachedData.senderNumber}"]`).length > 0) {
            $('#sender-id').val(cachedData.senderNumber);
        }
        //$('#email-body').val(cachedData.emailBody);
        emailBodyEditor.setData(cachedData.emailBody);
        sendLaterSwitch.setPosition(cachedData.sendLater);
        $('#schedule-container').toggle(cachedData.sendLater); // Toggle the section based on sendLater value
        addOptOutSwitch.setPosition(cachedData.allowOptout);
    }
}

/*function populateSender(burstSenders, selectedSender) {
    var isSelectedSender = false;
    $('#sender-id').empty();
    // Populate Sender Picklist - Default Picklist
    isSelectedSender = selectedSender == DEFAULT_SENDER ? true : false;
    $('#sender-id').append($('<option>', {
        value: burstSenders["default"],
        text: burstSenders["default"],
        selected: isSelectedSender
    }));
    // Populate Sender Picklist - Purchased Numbers
    $.each(burstSenders["purchased_numbers"], function (index, purchasedNumber) {
        isSelectedSender = String(selectedSender) == String(purchasedNumber) ? true : false;
        $('#sender-id').append($('<option>', {
            value: purchasedNumber,
            text: purchasedNumber,
            selected: isSelectedSender
        }));
    });
    // Populate Sender Picklist - Purchased IDs
    isSelectedSender = burstSenders["purchased_ids"].length && String(selectedSender) == String(burstSenders["purchased_ids"][0]) ? true : false;
    if (burstSenders["purchased_ids"].length) {
        $('#sender-id').append($('<option>', {
            value: burstSenders["purchased_ids"][0],
            text: burstSenders["purchased_ids"][0],
            selected: isSelectedSender
        }));
    }
}*/

function closeWidget() {
    ZOHO.CRM.UI.Popup.closeReload()
}