var orgInfo, currentUser, savedData;
var crmModules = [];

$(document).ready(async function () {
    showLoader();

    // Subscribe to the EmbeddedApp onPageLoad event before initializing 
    ZOHO.embeddedApp.on('PageLoad', async function (data) {

        //Hide alphanueric sender id option initially
        $('#sender-email').hide();

        // Initialize Switchery with a small size
        var twoWaySwitch = new Switchery(document.getElementById('two-way-toggle'), { size: 'small', color: '#54A4DA' });

        // Get OrgInfo and push to hidden fields
        var orgData = await ZOHO.CRM.CONFIG.getOrgInfo();
        orgInfo = orgData.org[0];

        // Push values to hidden fields
        $('#orgid').val(orgInfo.id);
        $('#zgid').val(orgInfo.zgid);
        $('#timezone').val(orgInfo.time_zone);

        // Get Modules List and push to Modules to enable field
        var moduleData = await ZOHO.CRM.META.getModules();
        console.log(moduleData);
        $.each(moduleData.modules, function (index, module) {
            if (isModuleEligible(module)) {
                crmModules[module.api_name] = { 'plural_label': module.plural_label, 'url_name': module.module_name };
                $('#email-modules').append($('<option>', {
                    value: module.api_name,
                    text: module.plural_label
                }));
            }
            // Assign EMAIL History Module URL name
            if (module.api_name == EMAIL_HISTORY_MODULE) {
                $('#email-history-module').val(module.module_name);
            }
        });

        // Get Current User info and push to hidden field
        var userData = await ZOHO.CRM.CONFIG.getCurrentUser();
        currentUser = userData.users[0]
        $('#login-userid').val(currentUser.email);

        //var defaultCountry = 'au';
        // Get saved data
        savedData = await getSavedSettings(orgInfo.id);
        if (savedData.success && savedData.data.zgid) {
            $('#sender').val(savedData.data.zepto_mail_sender_id);

            $('#error-email').val(savedData.data.error_email);
            $('#client-code').val(savedData.data.client_code);
            $('#email-modules').val(savedData.data.modules_for_zepto_mail);
            twoWayEnabled = savedData.data.extra_data['two_way_email'] ? savedData.data.extra_data['two_way_email'] : 'false';
            twoWayEnabled = twoWayEnabled == 'false' ? false : true;
            twoWaySwitch.setPosition(twoWayEnabled);
            // Trigger the change event to update the module mappings
            $('#email-modules').trigger('change');
        } else {
            twoWaySwitch.setPosition(true);
        }

        // Toggle password visibility
        $('.toggle-password').click(function () {
            const passwordInput = $(this).prev('input');

            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                $(this).addClass('text-[#54a4da]'); // Add color for visibility
            } else {
                passwordInput.attr('type', 'password');
                $(this).removeClass('text-[#54a4da]'); // Remove color for hidden state
            }
        });


        // Event delegation for dynamically generated fields
        $('#form-container').on('change', 'select[required]', function () {
            validateSelectField($(this));
        });

        // Additional event delegation for the "Modules to enable Email for" select field
        $('#form-container').on('change', '#email-modules', function () {
            validateSelectField($(this));
        });

        // Additional event delegation for the dynamically generated select fields in field-mapping-section
        $('#form-container').on('change', '.field-mapping-section select', function () {
            validateSelectField($(this));
        });

        // Add event listener to handle form submission
        $('#save-settings-button').on('click', function () {
            if (validateForm()) {
                // Form is valid, proceed with submission
                submitForm();
            }
        });

        // jQuery to handle modal show/hide
        $('#openModal').on('click', function () {
            $('#modal').removeClass('hidden');
        });

        $('#closeModal').on('click', function () {
            $('#modal').addClass('hidden');
        });

        hideLoader();
    });

    // Initializing the widget.
    //ZOHO.embeddedApp.init();
    ZOHO.embeddedApp.init().then(function () {
        ZOHO.CRM.UI.Resize({
            height: HEIGHT,
            width: WIDTH
        });
    });
    $('#email-modules').css('height', '100px');
});

$(document).on('change', '#email-modules', function () {
    // Update module mappings when "Modules to enable Email for" changes
    updateModuleMappings();
});

function updateModuleMappings() {
    var selectedModules = $('#email-modules').val();
    var moduleMappingsContainer = $('#module-mappings');

    for (const module in crmModules) {
        let isSelected = selectedModules.includes(module);
        let exists = moduleMappingsContainer.find('.field-mapping-section[data-module="' + module + '"]');
        if (isSelected && !exists.length) {
            createModuleMappingSection(module);
        } else if (!isSelected && exists.length) {
            exists.remove();
        }
    }
}

async function createModuleMappingSection(module) {
    showLoader();
    //console.log(module);
    // Check if savedData is present and the passed module is same as the saved one
    var isSavedModule = false;
    if (savedData && savedData.success && savedData.data.modules_for_zepto_mail && savedData.data.modules_for_zepto_mail.includes(module)) {
        isSavedModule = true;
    }

    // Fetch Fields metadata of the Module
    var moduleFields = await ZOHO.CRM.META.getFields({ 'Entity': module });
    //console.log(moduleFields);
    var section = $('.field-mapping-section-template')
        .clone()
        .attr('data-module', module)
        .removeClass('field-mapping-section-template')
        .addClass('field-mapping-section')
        .show();

    section.find('h3').text(crmModules[module]['plural_label']);

    // Create Picklist for Mobile Field Mapping
    var emailFieldPicklist = section.find('select.email-picklist').attr('name', 'email-field-mapping-' + module);
    $.each(moduleFields.fields, function (index, moduleField) {
        if (isEmailField(moduleField)) {
            var isSelected = (isSavedModule && moduleField.api_name === savedData.data.module_field_mappings['email_field_mapping_' + module]) ? true : false;
            emailFieldPicklist.append($('<option>', {
                value: moduleField.api_name,
                text: moduleField.display_label,
                selected: isSelected
            }));
        }
    });

    // Create Picklist for Email Opt Out Field Mapping
    var emailOptOutFieldPicklist = section.find('select.email-optout-picklist').attr('name', 'email-optout-field-mapping-' + module);
    $.each(moduleFields.fields, function (index, moduleField) {
        if (isBooleanField(moduleField)) {
            //console.log(moduleField.api_name);
            //console.log(savedData.data.module_field_mappings['email_optout_field_mapping_' + module]);
            var isSelected = (isSavedModule && moduleField.api_name === savedData.data.module_field_mappings['email_optout_field_mapping_' + module]) ? true : false;
            emailOptOutFieldPicklist.append($('<option>', {
                value: moduleField.api_name,
                text: moduleField.display_label,
                selected: isSelected
            }));
        }
    });

    // Create hidden input for Module Plural Label
    var modulePluralLabelInput = $('<input>', {
        type: 'hidden',
        name: 'module-plural-label-' + module,
        value: crmModules[module]['plural_label']
    });

    // Create hidden input for Module URL Name
    var moduleUrlNameInput = $('<input>', {
        type: 'hidden',
        name: 'module-url-name-' + module,
        value: crmModules[module]['url_name']
    });

    // Append the hidden inputs to the section
    section.append(modulePluralLabelInput).append(moduleUrlNameInput);

    // Add the section to the container
    $('#module-mappings').append(section);

    hideLoader();
}

function submitForm() {
    // Create an array to store form data
    var formData = [];

    // Serialize each form element manually
    $('#email-settings-form :input').each(function () {
        var field = $(this);
        var fieldName = field.attr('name');
        var fieldValue = field.val();

        // If the field is a multi-select, get all selected options
        if (field.is('select[multiple]')) {
            fieldValue = field.val();
        }

        // Add the 2-way switch to the formData array
        if (fieldName == 'two-way-toggle') {
            fieldValue = $('#two-way-toggle').prop('checked');
        }

        formData.push({ name: fieldName, value: fieldValue });
    });

    $.ajax({
        url: SQUIRREL_EXTENSION_PATH + SAVE_SETTINGS,
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
                showSuccessMessage(response.message);
            } else {
                // Show error message
                showErrorMessage(response.message);
            }
        },
        error: function (err) {
            // Show error message in case of AJAX failure
            showErrorMessage('Error submitting the form. Please try again.' + console.log(err));
        },
        complete: function () {
            // Hide full-page loader after request completion
            hideLoader();
        }
    });
}

function validateSelectField($selectField) {
    if ($selectField.val() == null || $selectField.val().length === 0) {
        $selectField.addClass('invalid');
    } else {
        $selectField.removeClass('invalid');
    }
}

function validateForm() {
    var isValid = true;

    // Reset previous validation styles
    $('input, select').removeClass('invalid');

    // Check mandatory fields
    $('input[required], select[required]').each(function () {
        var value = $(this).val();
        if (typeof value === 'string' && value.trim() === '' || ($(this).is('select') && !hasSelectedOption($(this)))) {
            isValid = false;
            $(this).addClass('invalid');
        }
        // Additional check for the "Error Email Address" field
        if ($(this).is('input[type="email"]')) {
            if (!isValidEmail($(this).val())) {
                isValid = false;
                $(this).addClass('invalid');
            }
        }
    });

    // Explicitly validate all select fields
    var emailModulesVal = $('#email-modules').val();
    if (!emailModulesVal || emailModulesVal == null || emailModulesVal == '') {
        isValid = false;
        $('#email-modules').addClass('invalid');
    }
    $('.field-mapping-section .email-picklist').each(function () {
        var value = $(this).val();
        if (typeof value === 'string' && value.trim() === '' || ($(this).is('select') && !hasSelectedOption($(this)))) {
            isValid = false;
            $(this).addClass('invalid');
        }
    });
    $('.field-mapping-section .email-optout-picklist').each(function () {
        var value = $(this).val();
        if (typeof value === 'string' && value.trim() === '' || ($(this).is('select') && !hasSelectedOption($(this)))) {
            isValid = false;
            $(this).addClass('invalid');
        }
    });

    // Validate sender-email field with proper email format
    var senderEmail = $('#sender').val();
    if (!isValidEmail(senderEmail)) {
        isValid = false;
        $('#sender').addClass('invalid');
    }

    if (!isValid) {
        // Scroll to the first invalid field (optional)
        $('html, body').animate({
            scrollTop: $('.invalid').first().offset().top - 20
        }, 500);
    }

    return isValid;
}

