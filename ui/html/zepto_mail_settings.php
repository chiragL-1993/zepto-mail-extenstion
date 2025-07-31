<!--<!DOCTYPE html>-->
<!--<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Burst SMS Settings</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://live.zwidgets.com/js-sdk/1.2/ZohoEmbededAppSDK.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/zepto_mail_settings.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://unpkg.com/tailwindcss-cdn@3.3.4/tailwindcss.js"></script>
    <link rel="stylesheet" href="css/common.css" />
    <link rel="stylesheet" href="countries/build/css/countrySelect.css">
    <script src="countries/build/js/countrySelect.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/abpetkov/switchery/dist/switchery.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/abpetkov/switchery/dist/switchery.min.js"></script>

    <style>
        .country-select .selected-flag {
            z-index: 0;
        }

        .sms-optout-picklist {
            max-width: 150px !important;
        }

        .mobile-picklist {
            max-width: 150px !important;
        }
    </style>
</head>-->
<style>
    .country-select .selected-flag {
        z-index: 0;
    }

    .email-optout-picklist {
        max-width: 150px !important;
    }

    .email-picklist {
        max-width: 150px !important;
    }
</style>

<body class="flex justify-center py-10 text-xs text-gray-800 bg-gray-100">

    <div id="form-container">

        <form id="email-settings-form" action="#" method="post" class="divide-y shadow-2xl w-[800px] rounded-lg divide-y bg-white relative">

            <header class="p-10 flex gap-5 items-center">
                <div class="shadow border rounded items-center flex px-4 py-3">
                    <i class="fa-solid fa-gear text-xl"></i>
                </div>
                <div>
                    <div class="font-bold text-lg">Zepto Mail Settings</div>
                    <div>It is crucial to configure these settings correctly for sending Mail. Incorrect settings may result in the failure to send your Mail.</div>
                </div>
            </header>

            <div class="space-y-3 p-5">

                <!--                <div class="grid grid-cols-3 gap-5">
                    <fieldset class="space-y-1 flex flex-col">
                        <label for="api-key" class="font-medium">Burst SMS API Key <span class="text-red-600">*</span></label>
                        <input type="text" id="api-key" name="api-key" required class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                    </fieldset>
                    <fieldset class="space-y-1 flex flex-col">
                        <label for="api-secret" class="font-medium">Burst SMS API Secret <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <input type="password" id="api-secret" name="api-secret" required class="rounded-md py-2 px-3 ring-1 ring-gray-300 focus:ring-blue-500 focus:border-blue-500 block w-full border-gray-300">
                            <button type="button" class="toggle-password absolute right-0 top-0 m-2 focus:outline-none focus:ring-opacity-75 rounded-md text-gray-400 hover:text-gray-500 focus:ring-blue-500">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </fieldset>
                    <fieldset class="space-y-1 flex flex-col">
                        <label for="country" class="font-medium">Send SMS to Country <span class="text-red-600">*</span></label>
                        <input type="text" id="country" name="country" required class="rounded-md py-2 px-3 ring-1 ring-gray-300 w-full">
                    </fieldset>
                </div>-->

                <div class="grid grid-cols-3 gap-5">
                    <fieldset class="space-y-1 flex flex-col group fieldset-tooltip">
                        <label for="sender" class="font-medium">Mail Sender ID <span class="text-red-600">*</span></label>
                        <input type="email" id="sender" name="sender" required class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                        <!--                        <select id="sender" name="sender" required class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                            <option value="" selected>Choose Sender</option>
                             Options will be dynamically populated by jQuery 
                        </select>-->
                        <!-- <input type="text" id="sender-email" name="sender-email" class="rounded-md py-2 px-3 ring-1 ring-gray-300"> -->
                        <!-- <div class="tooltip-text">
                            Choose a default Sender ID from the available options of Shared Number, Dedicated Virtual Mobile Numbers, or Alphanumeric Sender ID.
                            <br />
                            For Alphanumeric Sender ID, please type the exact ID in the input field. It must match the purchased ID to ensure successful SMS delivery.
                            <br />
                            <b>Note:</b> The number/id set here will also be used for messages sent via webhook.
                        </div> -->
                    </fieldset>
                    <fieldset class="space-y-1 flex flex-col">
                        <label for="client-code" class="font-medium">Squirrel Client Code <span class="text-red-600">*</span></label>
                        <input type="text" id="client-code" name="client-code" required class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                    </fieldset>
                    <fieldset class="space-y-1 flex flex-col">
                        <label for="error-email" class="font-medium">Error Email Address <span class="text-red-600">*</span></label>
                        <input type="email" id="error-email" name="error-email" required class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                    </fieldset>
                </div>

                <div class="grid grid-cols-3 gap-5 !mt-4">
                    <fieldset class="relative flex flex-col space-y-2 group fieldset-tooltip">
                        <div class="flex items-center">
                            <label for="two-way-toggle" class="font-medium flex-shrink-0 mr-2">Enable 2-Way Email? <span class="text-red-600">*</span></label>
                            <input type="checkbox" id="two-way-toggle" name="two-way-toggle" class="two-way-toggle">
                        </div>
                        <div class="tooltip-text">
                            2-Way Mail will not work with Alphanumeric Sender ID.
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="p-5 space-y-5">
                <fieldset class="space-y-3 flex flex-col">
                    <!-- Field 6: Modules to enable Email for -->
                    <label for="email-modules" class="block font-medium">Modules to enable Zepto Mail for <span class="text-red-600">*</span> <span class="text-gray-400">(choose multiple options by holding command/ctrl key)</span></label>
                    <select id="email-modules" name="email-modules" multiple required class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                        <!-- Options will be dynamically populated by jQuery -->
                    </select>
                </fieldset>

                <!-- Modules Field Mappings Section -->
                <div id="module-mappings" class="grid grid-cols-4 gap-5"></div>

                <!-- Open Modal -->
                <div class="mt-0">
                    <span>Want to setup a webhook for sending the Zepto Mail? <span id="openModal" class="text-blue-500 hover:text-sky-500 underline cursor-pointer">click here</span> for the instructions.</span>
                </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" id="orgid" name="orgid">
            <input type="hidden" id="zgid" name="zgid">
            <input type="hidden" id="timezone" name="timezone">
            <input type="hidden" id="login-userid" name="login-userid">
            <input type="hidden" id="email-history-module" name="email-history-module">

            <footer class="p-5 flex justify-between items-center">
                <div>
                    <div id="success-message" class="text-green-700" style="display: none">Settings saved successfully!</div>
                    <div id="error-message" class="text-red-500" style="display: none">Failed to save settings. Please try again.</div>
                </div>

                <!-- Submit Button -->
                <input type="button" value="Save Settings" id="save-settings-button" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer">
            </footer>

            <div id="loader-overlay" style="display: none;" class="fixed inset-0 flex items-center bg-black opacity-50 justify-center rounded-lg">
                <i class="fa-solid fa-spinner animate-spin text-8xl text-white"></i>
            </div>

        </form>

        <!-- Modal Container -->
        <div id="modal" class="fixed top-0 left-0 w-full h-full flex items-center justify-center hidden">
            <!-- Modal Overlay -->
            <div class="absolute w-full h-full bg-gray-800 opacity-50 z-50"></div>
            <!-- Modal Content -->
            <div class="bg-white p-8 rounded shadow-lg w-4/5 z-50 relative">
                <!-- Close Button (Top Right) -->
                <button id="closeModal" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <!-- Modal Header -->
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Webhook Configurations</h2>
                </div>
                <!-- Modal Body -->
                <div class="mb-2">
                    <strong>URL to Notify: </strong>
                    <span class="text-gray-500">
                        <a href="https://email-extension.au.squirrel.biz/webhook" target="_blank" class="underline text-gray-500 hover:text-blue-500">https://email-extension.au.squirrel.biz/webhook</a>
                    </span>
                    <span> (Method: Post)</span>
                </div>
                <div>
                    <span><strong>Webhook Parameters: </strong></span>
                </div>
                <div class="mb-1">
                    <span>
                        Do not enter parameters in the Header section.
                        In the Body section select Type = "Form Data" to input all the parameters given below. <br />
                    </span>
                </div>
                <div class="shadow-lg rounded-lg overflow-hidden">
                    <table class="w-full table-fixed">
                        <thead>
                            <tr class="bg-[#54a4da]">
                                <th class="w-1/6 py-2 px-4 text-left text-white font-bold uppercase">Parameter Name</th>
                                <th class="w-5/6 py-2 px-2 text-left text-white font-bold uppercase">Parameter Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr>
                                <td colspan="2" class="py-1 px-4 border-b border-gray-200" style="background-color: aliceblue; font-weight: 300;">
                                    Input all parameters given below under Module Parameters.
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 px-4 border-b border-gray-200 font-bold">org_id</td>
                                <td class="py-1 px-2 border-b border-gray-200">
                                    Parameter Type: Organisation<br />
                                    Parameter Value: Organisation Id
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 px-4 border-b border-gray-200 font-bold">user_id</td>
                                <td class="py-1 px-2 border-b border-gray-200">
                                    Parameter Type: Users<br />
                                    Parameter Value: User Id
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 px-4 border-b border-gray-200 font-bold">record_id</td>
                                <td class="py-1 px-2 border-b border-gray-200">
                                    Parameter Type: Choose required module (eg. Leads)<br />
                                    Parameter Value: Choose id field of the module (eg. Lead Id)
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="py-1 px-4 border-b border-gray-200" style="background-color: aliceblue; font-weight: 300;">
                                    Input all parameters given below under Custom Parameters.
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 px-4 border-b border-gray-200 font-bold">module_api_name</td>
                                <td class="py-1 px-2 border-b border-gray-200">
                                    Parameter Value: API Name of the module for which you want to configure webhook for (eg. Parents_Guardians)<br />
                                    <span class="text-gray-500">[To view api name of a module, Go to Setup -> Developer Hub (APIs & SDKs) -> API Names -> Copy API Name of the relevant module]</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 px-4 border-b border-gray-200 font-bold">email_template_id</td>
                                <td class="py-1 px-2 border-b border-gray-200">
                                    Parameter Value: ID of the Email Template record which you want to use for Email content (eg. 7874000008845012)<br />
                                    <span class="text-gray-500">
                                        [Go to Email Template module & click on the relevant Email Template record. Copy the ID from the URL as highlighted below<br />
                                        https://crm.zoho.com.au/crm/org000342300/tab/EmailTemplates/<strong>7874000008845012</strong>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="field-mapping-section-template border rounded-lg space-y-3 p-3" style="display: none;">
        <h3 class="font-medium"></h3>

        <fieldset class="flex flex-col space-y-1">
            <label>Email Field <span class="text-red-600">*</span></label>
            <select name="email-field-mapping-Contacts" class="email-picklist rounded-md py-2 px-3 ring-1 ring-gray-300">
                <option value=""></option>
            </select>
        </fieldset>

        <fieldset class="flex flex-col space-y-1">
            <label>Email Opt Out Field <span class="text-red-600">*</span></label>
            <select name="email-optout-field-mapping-Contacts" class="email-optout-picklist rounded-md py-2 px-3 ring-1 ring-gray-300">
                <option value=""></option>
            </select>
        </fieldset>
    </div>

</body>

<!--</html>-->