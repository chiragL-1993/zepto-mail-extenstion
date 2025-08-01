const MODULES_TO_INCLUDE = ['Leads', 'Contacts', 'Deals'];

const USER_MODULE = 'Users';

const EXTENSION_NAMESPACE = 'zeptomailwidgetdemo__';

const EMAIL_HISTORY_MODULE = EXTENSION_NAMESPACE + 'Email_Historys';
const EMAIL_TEMPLATE_MODULE = EXTENSION_NAMESPACE + 'EmailTemplates';
const EMAIL_SETTINGS_WEBTAB = EXTENSION_NAMESPACE + 'Zepto_Mail_Settings';

const EMAIL_TEMPLATE_FIELDS = [
    'id',
    'Name',
    EXTENSION_NAMESPACE + 'Module',
    EXTENSION_NAMESPACE + 'Email_Templates_Body',
    'Owner',
    'Modified_By',
    EXTENSION_NAMESPACE + 'Status',
    EXTENSION_NAMESPACE + 'Email_Templates_Subject',
];
const EMAIL_HISTORY_FIELDS = [
    'id',
    'Name',
    EXTENSION_NAMESPACE + 'Recipient_Number',
    EXTENSION_NAMESPACE + 'Status',
    EXTENSION_NAMESPACE + 'Custom_Module_Name',
    EXTENSION_NAMESPACE + 'Custom_Module_Record_ID',
    EXTENSION_NAMESPACE + 'Type'
];
const EMAIL_HISTORY_CM_Name_FIELD = EMAIL_HISTORY_FIELDS[4];
const EMAIL_HISTORY_CM_ID_FIELD = EMAIL_HISTORY_FIELDS[5];

const CREATE_ACTION_BUTTONS = ['ListViewWithoutRecord', 'CreateOrCloneView'];
const SINGLE_RECORD_ACTION_BUTTONS = ['ListViewEachRecord', 'DetailView'];

const SQUIRREL_EXTENSION_PATH = 'https://scripts.squirrelcrmhub.com.au/zoho_scripts/marketplace/zepto-mail-extenstion/api/';
//const SQUIRREL_EXTENSION_PATH = 'http://localhost/zepto-email-extenstion/api/';
const SAVE_SETTINGS = 'save_settings.php';
const GET_SETTINGS = 'get_settings.php';
const SEND_SINGLE_EMAIL = 'send_single_email.php';
const SEND_BULK_EMAIL = 'send_bulk_email.php';
const GET_SCHEDULED_JOBS = 'get_scheduled_email_batches.php';
const WITHDRAW_SCHEDULED_JOB = 'withdraw_scheduled_email_batch.php';
const GET_BURST_NUMBERS = 'get_burst_numbers.php';

// Number Type mapping from libphonenumbers-js lib
const PHONE_NUMBER_TYPE = [
    'FIXED_LINE',
    'MOBILE',
    'FIXED_LINE_OR_MOBILE',
    'TOLL_FREE',
    'PREMIUM_RATE',
    'SHARED_COST',
    'VOIP',
    'PERSONAL_NUMBER',
    'PAGER',
    'UAN',
    'VOICEMAIL',
    'UNKNOWN'
]
const DEFAULT_COUNTRY_CODE = 'AU';

const OPTOUT_TEXT = '\nTo opt-out reply STOP';

const DEFAULT_SENDER = 'SHARED EMAIL ID';
const ALPHANUMERIC_SENDER = 'Alphanumeric Sender ID';

const MERGE_FIELD_TYPES_TO_EXCLUDE = ['profileimage', 'fileupload', 'rollup_summary', 'subform'];

// Show Spinner
const showLoader = function () {
    // Show the full-page overlay with spinner
    $('#loader-overlay').show();
}

// Hide Spinner
const hideLoader = function () {
    // Hide the full-page overlay with spinner
    $('#loader-overlay').hide();
}

// Show success/info message for 10 seconds
const showSuccessMessage = function (message) {
    $('#success-message').text(message).fadeIn();
    setTimeout(function () {
        $('#success-message').fadeOut();
    }, 10000); // 10 seconds
}

// Show error/warning message for 10 seconds
const showErrorMessage = function (message) {
    $('#error-message').text(message).fadeIn();
    setTimeout(function () {
        $('#error-message').fadeOut();
    }, 10000); // 10 seconds
}

// Open a confirmation dialog to alert user before proceeding further
const showConfirmationDialog = function (message, callback) {
    var confirmationMessage = message;
    var dialogHtml = `
        <div id="confirmationDialog" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white border border-gray-300 p-6 w-96 shadow-md z-50">
            <div>${confirmationMessage}</div>
            <div class="flex justify-between mt-4">
                <button class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer" id="confirmYes">Yes</button>
                <button class="rounded-md bg-gray-400 px-4 py-2 text-white hover:bg-gray-500 cursor-pointer" id="confirmNo">No</button>
            </div>
        </div>`;

    $('body').append(dialogHtml);

    $('#confirmYes').on('click', function () {
        $('#confirmationDialog').remove();
        callback(true);
    });

    $('#confirmNo').on('click', function () {
        $('#confirmationDialog').remove();
        callback(false);
    });
}

// Function to check if the select field has a selected option    
const hasSelectedOption = function ($selectField) {
    return $selectField.find('option:selected').length > 0 || $selectField.val() !== null;
}

// Ajax request to get saved email settings from Squirrel DB
const getSavedSettings = async function (orgId) {
    return $.ajax({
        url: SQUIRREL_EXTENSION_PATH + GET_SETTINGS + '?orgid=' + orgId,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            // Process the response
            if (!response.success) {
                showErrorMessage(response.message);
            }
            return response;
        },
        error: function () {
            // Show error message in case of AJAX failure
            showErrorMessage('Error getting saved settings. Please try again.');
        }
    });
}

// Function to check if all the Email Settings are configured properly
function isSettingsPresent(savedData, module = '') {
    var checkForModule = module ? 'checkForModule' : 'dontCheckForModule';
    var isSettingsPresent = false;
    console.log(checkForModule);
    switch (checkForModule) {
        case 'checkForModule':
            if (savedData.data
                && savedData.data.zgid
                && savedData.data.zepto_mail_api_key
                && savedData.data.modules_for_zepto_mail
                && savedData.data.modules_for_zepto_mail.includes(module)
                && savedData.data.module_field_mappings
                && savedData.data.module_field_mappings['email_field_mapping_' + module]
                && savedData.data.module_field_mappings['email_optout_field_mapping_' + module]
            ) {
                isSettingsPresent = true;
            }
            break;

        case 'dontCheckForModule':
            if (savedData.data
                && savedData.data.zgid
                && savedData.data.zepto_mail_api_key
                && savedData.data.modules_for_zepto_mail
            ) {
                isSettingsPresent = true;
            }
            break;

        default:
            isSettingsPresent = false;
    }

    return isSettingsPresent;
}

// Redirect to Email Settings Webtab
const openSettingsWebtab = async function () {
    // Fetch Fields metadata of the User
    await ZOHO.CRM.UI.Widget.open({ Entity: EMAIL_SETTINGS_WEBTAB, Message: {} });
    await ZOHO.CRM.UI.Popup.close();
}

// Function to validate email address format
const isValidEmail = function (email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Function to check if a module is eligible for the extension
const isModuleEligible = function (module) {
    if (
        MODULES_TO_INCLUDE.includes(module.api_name)
        || (module.api_supported
            && !module.api_name.toLowerCase().includes('extension')
            && module.generated_type == 'custom')
    ) {
        return true;
    }
    return false;
}

// Function to check is the field is a phone type field
const isPhoneField = function (field) {
    if (field.data_type == 'phone') {
        return true
    }
    return false;
}

// Function to check is the field is a phone type field
const isEmailField = function (field) {
    if (field.data_type == 'email') {
        return true
    }
    return false;
}

// Function to check is the field is a boolean type field
const isBooleanField = function (field) {
    if (field.data_type == 'boolean') {
        return true
    }
    return false;
}

// This function is same as PHP's nl2br() with default parameters.
const nl2br = function (str, replaceMode, isXhtml) {
    var breakTag = (isXhtml) ? '<br />' : '<br>';
    var replaceStr = (replaceMode) ? '$1' + breakTag : '$1' + breakTag + '$2';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, replaceStr);
}

// Validates and Format a Phone Number according to the Country Code
/*async function validatePhoneNumber(phoneNumber, countryCode) {

    if (!phoneNumber) {
        return { isEmpty: true, isValid: false };
    }

    var phoneUtil = libphonenumber.PhoneNumberUtil.getInstance();
    var parsedNumber = phoneUtil.parse(phoneNumber, countryCode);

    if (!phoneUtil.isValidNumber(parsedNumber)) {
        return { isEmpty: false, isValid: false };
    }

    return {
        isEmpty: false,
        isValid: true,
        formattedNumber: phoneUtil.format(parsedNumber, libphonenumber.PhoneNumberFormat.E164),
        countryCode: parsedNumber.getCountryCode(),
        nationalNumber: parsedNumber.getNationalNumber(),
        numberType: PHONE_NUMBER_TYPE[phoneUtil.getNumberType(parsedNumber)]
    }

}*/

// Validates Merge Fields if not of the selected module & user module 
function validateMergeFields(text, module1, module2) {
    const validPatterns = new RegExp(`^\\$\\{(${module1}|${module2})\.[a-zA-Z0-9_]+}$`);
    const mergeFields = text.match(/\$\{[^\}]+\}/g);

    if (!mergeFields) {
        return true; // No merge fields found, nothing to validate
    }

    for (const field of mergeFields) {
        if (!validPatterns.test(field)) {
            return false;
        }
    }

    return true; // No invalid merge fields found
}

/*function calculateEmailCredits(text) {
    // Constants
    const standardMaxLength = 160;
    const concatenatedMaxLength = 153;
    const unicodeStandardMaxLength = 70;
    const unicodeConcatenatedMaxLength = 67;
    const escapeCharacters = ['|', '^', '€', '[', ']', '~']; // removed curly braces '{' and '}' as we are using these for variables

    // Calculate Email length based on escape characters
    function calculateLengthWithEscapeCharacters(str) {
        let length = 0;
        for (let i = 0; i < str.length; i++) {
            if (escapeCharacters.includes(str[i])) {
                length += 2; // Escape characters count as 2
            } else {
                length += 1; // Standard characters count as 1
            }
        }
        return length;
    }

    // Determine the type of Email (Standard or Unicode) based on the presence of Unicode characters
    function determineEmailType(str) {
        return /[^\u0000-\u007F]/.test(str) ? 'unicode' : 'standard';
    }

    // Calculate the number of credits required
    function calculateCredits(str) {
        const emailType = determineEmailType(str);
        const length = calculateLengthWithEscapeCharacters(str);

        if (emailType === 'unicode') {
            return (length <= 5000 ? Math.ceil(length / unicodeStandardMaxLength) : Math.ceil(length / unicodeConcatenatedMaxLength));
        } else {
            return (length <= 10000 ? Math.ceil(length / standardMaxLength) : Math.ceil(length / concatenatedMaxLength));
        }
    }

    // Main function
    const credits = calculateCredits(text);
    return credits;
}*/

/**
 * Calculate email credits for Zepto Mail
 * @param {Array|string} recipients - Single email or array of recipient emails
 * @returns {number} Email credits required (1 per recipient)
 */
function calculateEmailCredits(recipients) {
    if (!recipients) return 0;

    if (typeof recipients === 'string') {
        return 1; // Single recipient
    }

    if (Array.isArray(recipients)) {
        return recipients.length;
    }

    return 0;
}

// Processes array in chunk with the given chunk size
async function processArrayInChunks(array, chunkSize, callback) {
    let continueProcessing = true;
    for (let i = 0; i < array.length && continueProcessing; i += chunkSize) {
        const chunk = array.slice(i, i + chunkSize);
        try {
            await callback(chunk);
        } catch (error) {
            continueProcessing = false;
        }
    }
}

// Store JSON data in local storage
function saveToLocalStorage(key, data) {
    const jsonData = JSON.stringify(data);
    localStorage.setItem(key, jsonData);
}

// Retrieve JSON data from local storage, return false if not found
function getFromLocalStorage(key) {
    const jsonData = localStorage.getItem(key);
    if (jsonData === null) {
        return false;
    }
    const data = JSON.parse(jsonData);
    return data;
}

// Remove an item from local storage
function removeFromLocalStorage(key) {
    localStorage.removeItem(key);
}

// Ajax request to get purchased numbers from Burst Email
/*const getBurstNumbers = async function (apiKey, apiSecret, alphanuericId) {
    return $.ajax({
        url: SQUIRREL_EXTENSION_PATH + GET_BURST_NUMBERS + '?api_key=' + encodeURIComponent(apiKey) + '&api_secret=' + encodeURIComponent(apiSecret) + '&alphanumeric_id=' + encodeURIComponent(alphanuericId),
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            // Process the response
            if (!response.success) {
                showErrorMessage(response.message);
            }
            return response;
        },
        error: function () {
            // Show error message in case of AJAX failure
            showErrorMessage('Error getting burst numbers. Please try again.');
        }
    });
}*/