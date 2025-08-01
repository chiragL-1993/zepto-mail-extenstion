var entityId, orgId, buttonPosition;
var isModuleSelected = false;
let emailTemplateEditor;
const ERROR_TEXT = {
    //emailLength: 'Email body length exceeds the max character limit allowed by Burst, please shorten it',
    invalidMergeFields: 'Invalid merge fields found, please check and remove the invalid merge fields'
}

$(document).ready(async function () {
    showLoader();

    $(function () {
        ClassicEditor
            .create(document.querySelector('#email-template-body'))
            .then(editor => {
                emailTemplateEditor = editor;
            })
            .catch(error => {
                console.error(error);
            });
    });
    $('div.ck-powered-by-balloon').remove();
    // Subscribe to the EmbeddedApp onPageLoad event before initializing 
    ZOHO.embeddedApp.on('PageLoad', async function (data) {

        $('#module-name').on('change', function () {
            var $moduleSelect = $(this);
            var selectedValue = $moduleSelect.val();
            var previousValue = $('#module-name').data('prev-module');

            if (isModuleSelected) {
                var confirmationMessage = "Selecting a new module will clear the Email Template Body. Are you sure you want to proceed?";
                showConfirmationDialog(confirmationMessage, function (proceed) {
                    if (proceed) {
                        // Save the current module for future reference & populate merge fields
                        $('#module-name').data('prev-module', selectedValue);
                        $('#email-template-body').val(''); // Clear Email Template Body
                        populateModuleField($moduleSelect.val(), 'module-merge-fields');
                    } else {
                        // Reset the selected option if the user cancels
                        $moduleSelect.val(previousValue);
                    }
                });
            } else {
                // Save the current module for future reference & populate merge fields
                $('#module-name').data('prev-module', selectedValue);
                populateModuleField($moduleSelect.val(), 'module-merge-fields');
            }
            isModuleSelected = true;
        });

        // Get OrgInfo
        var orgData = await ZOHO.CRM.CONFIG.getOrgInfo();
        orgId = orgData.org[0].id;

        // Get saved Email Settings
        var savedData = await getSavedSettings(orgId);
        if (savedData.success && isSettingsPresent(savedData)) {
            var savedModules = savedData.data.modules_for_zepto_mail;

            // Get Modules List to fetch Display Label
            var modulesData = await ZOHO.CRM.META.getModules();
            var moduleList = [];
            $.each(modulesData.modules, function (index, module) {
                if (savedModules.includes(module.api_name)) {
                    var moduleData = {
                        value: module.api_name,
                        text: module.plural_label
                    }
                    moduleList.push(moduleData);
                }
            });

            // Populate Modules in the picklist
            $.each(moduleList, function (index, module) {
                $('#module-name').append($('<option>', {
                    value: module.value,
                    text: module.text
                }));
            });

            // Populate User Merge Fields
            await populateModuleField(USER_MODULE, 'user-merge-fields');

            // Check button position to know if it's an update request
            // Populate fields if update request
            buttonPosition = data.ButtonPosition;
            if (SINGLE_RECORD_ACTION_BUTTONS.includes(buttonPosition)) {
                entityId = data.EntityId[0];
                var templateRecord = await ZOHO.CRM.API.getRecord({ 'Entity': EMAIL_TEMPLATE_MODULE, 'RecordID': entityId });
                $('#email-template-name').val(templateRecord.data[0][EMAIL_TEMPLATE_FIELDS[1]]);
                $('#module-name').val(templateRecord.data[0][EMAIL_TEMPLATE_FIELDS[2]]);
                //$('#email-template-body').val(templateRecord.data[0][EMAIL_TEMPLATE_FIELDS[3]]);
                emailTemplateEditor.setData(templateRecord.data[0][EMAIL_TEMPLATE_FIELDS[3]]);
                $('#email-template-subject').val(templateRecord.data[0][EMAIL_TEMPLATE_FIELDS[7]]);
                $('#status').val(templateRecord.data[0][EMAIL_TEMPLATE_FIELDS[6]]);
                // Trigger the change event to populate the module merge fields
                $('#module-name').trigger('change');
            }

        } else {
            // If Email Settings not present, show error
            hideLoader();
            $('#form-container').hide();
            $('#error-container').show();
            $('#settings-error-message').show();
        }

        // Insert selected option into the textarea
        $('#module-merge-fields').on('change', function () {
            var selectedModule = $('#module-name').val();
            insertMergeField($(this), selectedModule);
        });
        $('#user-merge-fields').on('change', function () {
            insertMergeField($(this), USER_MODULE);
        });

        // Add event listener to display settings widget
        $('#open-settings').on('click', function () {
            openSettingsWebtab();
        });

        // Add event listener to handle form submission
        $('#save-template-button').on('click', function () {
            if (validateForm()) {
                // Form is valid, proceed with submission
                submitForm();
            }
        });

        hideLoader();
    });

    // Initializing the widget.
    ZOHO.embeddedApp.init();

});

async function populateModuleField(module, elementId) {
    showLoader();

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

    hideLoader();
}
function insertMergeField($mergeField, selectedModule) {
    if (!emailTemplateEditor) return;

    const selectedOption = $mergeField.val();
    if (!selectedOption) return;

    const mergeTag = '${' + selectedModule + '.' + selectedOption + '}';

    emailTemplateEditor.model.change(writer => {
        const insertPosition = emailTemplateEditor.model.document.selection.getFirstPosition();
        writer.insertText(mergeTag, insertPosition);
    });

    $mergeField.val(''); // Reset the dropdown
}
/*function insertMergeField($mergeField, selectedModule) {
    if (!emailTemplateEditor) return;

    const selectedOption = $mergeField.val();
    const mergeTag = '${' + selectedModule + '.' + selectedOption + '}';

    const viewFragment = emailTemplateEditor.data.processor.toView(mergeTag);
    const modelFragment = emailTemplateEditor.data.toModel(viewFragment);

    emailTemplateEditor.model.change(writer => {
        const selection = emailTemplateEditor.model.document.selection;
        writer.insert(modelFragment, selection.getFirstPosition());
    });

    $mergeField.val(''); // Reset the dropdown
}*/
/*function insertMergeField($mergeField, selectedModule) {
    var cursorPosition = $('#email-template-body').prop('selectionStart');
    var selectedOption = $mergeField.val();
    selectedOption = '${' + selectedModule + '.' + selectedOption + '}';

    if (cursorPosition === undefined) {
        // If cursor position is undefined, append the selected option at the end
        $('#email-template-body').val($('#email-template-body').val() + selectedOption);
    } else {
        // Insert the selected option at the cursor position
        var currentValue = $('#email-template-body').val();
        $('#email-template-body').val(currentValue.substring(0, cursorPosition) + selectedOption + currentValue.substring(cursorPosition));
    }

    // Reset the select to the default option after inserting the value
    $mergeField.val('');
}*/

// Validation function
function validateForm() {
    var isValid = true;

    // Reset previous validation styles
    $('input, select, textarea').removeClass('invalid');
    $('#email-error').hide();

    // Check mandatory fields
    $('input[required], select[required]').each(function () {
        var value = $(this).val();
        if (typeof value === 'string' && value.trim() === '' || ($(this).is('select') && !hasSelectedOption($(this)))) {
            isValid = false;
            $(this).addClass('invalid');
        }
    });

    /*if ($('#email-template-body').val().trim()) {
        let emailBody = $('#email-template-body').val();
        let selectedModule = $('#module-name').val();
        const creditsRequired = calculateEmailCredits(emailBody);
        const isValidMergeFields = validateMergeFields(emailBody, USER_MODULE, selectedModule);
        if (creditsRequired > 4) {
            isValid = false;
            $('#email-template-body').addClass('invalid');
            $('#email-error').show();
            $('#email-error-text').text(ERROR_TEXT.emailLength);
        } else if (!isValidMergeFields) {
            isValid = false;
            $('#email-template-body').addClass('invalid');
            $('#email-error').show();
            $('#email-error-text').text(ERROR_TEXT.invalidMergeFields);
        }
    }*/
    if (emailTemplateEditor) {
        let emailBody = emailTemplateEditor.getData().trim();
        let selectedModule = $('#module-name').val();
        //const creditsRequired = calculateEmailCredits(emailBody);
        const isValidMergeFields = validateMergeFields(emailBody, USER_MODULE, selectedModule);

        // Strip HTML and merge fields for validation
        let textOnly = $('<div>').html(emailBody).text().replace(/\${.*?}/g, '').trim();

        if (textOnly) {
            /*if (creditsRequired > 10) {
                isValid = false;
                $('#email-error').show();
                $('#email-error-text').text(ERROR_TEXT.emailLength);
            }*/
            if (!isValidMergeFields) {
                isValid = false;
                $('#email-error').show();
                $('#email-error-text').text(ERROR_TEXT.invalidMergeFields);
            } else {
                $('#email-error').hide(); // Valid input
            }
        } else {
            isValid = false;
            $('#email-error').show();
            $('#email-error-text').text("Email content is empty.");
        }
    }

    if (!isValid) {
        // Scroll to the first invalid field (optional)
        $('html, body').animate({
            scrollTop: $('.invalid').first().offset().top - 20
        }, 500);
    }

    return isValid;
}

// Placeholder for form submission logic
async function submitForm() {
    showLoader();
    // Get Current User data to assign as record owner or modifier
    var currentUser = await ZOHO.CRM.CONFIG.getCurrentUser();

    // Get submitted data
    var templateData = {};
    templateData[EMAIL_TEMPLATE_FIELDS[1]] = $('#email-template-name').val();
    templateData[EMAIL_TEMPLATE_FIELDS[2]] = $('#module-name').val();
    templateData[EMAIL_TEMPLATE_FIELDS[3]] = emailTemplateEditor.getData(); //$('#email-template-body').val();
    templateData[EMAIL_TEMPLATE_FIELDS[7]] = $('#email-template-subject').val();
    templateData[EMAIL_TEMPLATE_FIELDS[6]] = $('#status').val();
    console.log(templateData);
    if (!entityId) {
        // Create Email Template Record
        templateData[EMAIL_TEMPLATE_FIELDS[4]] = currentUser.users[0].id;
        var response = await ZOHO.CRM.API.insertRecord({ Entity: EMAIL_TEMPLATE_MODULE, APIData: templateData });
    } else {
        // Update Email Template Record
        templateData[EMAIL_TEMPLATE_FIELDS[5]] = currentUser.users[0].id;
        templateData['id'] = entityId;
        var response = await ZOHO.CRM.API.updateRecord({ Entity: EMAIL_TEMPLATE_MODULE, APIData: templateData });
    }

    // Redirect on success or show error on failire
    if (response.data[0].code === 'SUCCESS') {
        if (buttonPosition === 'DetailView') {
            await ZOHO.CRM.UI.Popup.closeReload();
        } else {
            await ZOHO.CRM.UI.Record.open({ Entity: EMAIL_TEMPLATE_MODULE, RecordID: response.data[0].details.id });
        }
    } else {
        hideLoader();
        showErrorMessage(`Error creating EMail Template Record - ${response.data[0].message}`)
    }
}