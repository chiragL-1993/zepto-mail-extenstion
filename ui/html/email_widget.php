<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send SMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/abpetkov/switchery/dist/switchery.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://unpkg.com/tailwindcss-cdn@3.3.4/tailwindcss.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/gh/abpetkov/switchery/dist/switchery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/google-libphonenumber@3.2.33/dist/libphonenumber.min.js"></script>
    <script src="https://live.zwidgets.com/js-sdk/1.2/ZohoEmbededAppSDK.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/sms_widget.js"></script>
    <link rel="stylesheet" href="css/common.css" />
</head> -->

<body class="text-xs text-gray-800">

    <div id="error-container" style="display: none;" class="m-5 p-10 rounded border flex justify-between items-center divide-y">
        <div id="settings-error-message" class="message error">Please update your Zepto Email settings to begin sending messages.</div>
        <button type="button" id="open-settings" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer">Update Settings</button>
    </div>

    <div id="form-container">

        <form id="send-email-form" action="#" method="post" class="divide-y">

            <header class="p-5 flex space-x-5 items-center">
                <div class="shadow border rounded items-center flex px-3 py-2">
                    <i class="fa-regular fa-envelope text-xl"></i>
                </div>
                <p class="text-gray-600">
                    To initiate an Email, choose from the template library or craft a custom Email body. Opt for immediate delivery or schedule it for a specific date and time.
                    <br /><span>Note: Timezone will be taken from your CRM timezone settings.</span>
                </p>
            </header>

            <section class="space-y-3 p-5">
                <!-- View Email Recipient List -->
                <fieldset class="is-view-email grid grid-cols-3">
                    <label for="recipient-list" class="font-medium mt-1">View</label>
                    <select name="saved-view" id="savedViews" class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300"></select>
                    </select>
                </fieldset>
                <!-- Field 1: Campaign Name -->
                <fieldset id="campaign-container" class="grid grid-cols-3" style="display: none">
                    <label for="campaign-name" class="font-medium mt-1">Campaign Name</label>
                    <input type="text" id="campaign-name" name="campaign-name" class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300" placeholder="eg Kindergarten Offer 2022">
                </fieldset>

                <!-- Field 2: Message Template -->
                <fieldset class="grid grid-cols-3">
                    <label for="message-template" class="font-medium mt-1">Email Template</label>
                    <select id="message-template" name="message-template" class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300">
                        <option value="" selected>Choose Template</option>
                        <!-- Options will be dynamically populated by jQuery -->
                    </select>
                </fieldset>
            </section>

            <section class="p-5 space-y-3">
                <!-- Field 3: Send it later toggle switch using Switchery -->
                <fieldset class="toggle-container grid grid-cols-3">
                    <label class="toggle-label font-medium mt-1" for="send-later-toggle">Send It Later</label>
                    <div class="toggle-switch-container col-span-2">
                        <input type="checkbox" id="send-later-toggle" class="send-later-toggle">
                    </div>
                </fieldset>

                <!-- Field 4: Schedule datetime picker using Flatpickr -->
                <fieldset id="schedule-container" class="grid grid-cols-3" style="display: none;">
                    <label for="schedule-date" class="font-medium col-span-1 mt-1">Schedule <span class="text-red-500">*</span></label>
                    <input type="text" id="schedule-date" class="datetime-picker rounded-md py-2 px-3 ring-1 ring-gray-300" data-input>
                    <span id="timezone" class="py-2 px-2 text-gray-400"></span>
                </fieldset>
            </section>

            <section class="p-5 space-y-3">
                <!-- Field 5: Choose Sender Number/Name -->
                <fieldset class="grid grid-cols-3">
                    <label for="sender-id" class="font-medium mt-1">Sender Email <span class="text-red-500">*</span></label>
                    <input type="email" id="sender-id" name="sender-id" required class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300">
                    <!-- <select id="sender-id" name="sender-id" required class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300">
                        <option value="" selected>Choose Sender</option>
                         Options will be dynamically populated by jQuery -->
                    <!-- </select> -->
                </fieldset>

                <!-- Field 6: Choose Email History Owner -->
                <fieldset class="grid grid-cols-3 group fieldset-tooltip">
                    <label for="email-owner" class="font-medium mt-1">Email History Owner <span class="text-red-500">*</span></label>
                    <select id="email-owner" name="email-owner" required class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300">
                        <option value="Record Owner" selected>Record Owner</option>
                        <option value="Logged in User" selected>Logged in User</option>
                    </select>
                    <div class="tooltip-text" style="left: 33% !important;">
                        Select Owner of the Email History Record.
                        <br />User merge fields will also be taken from the selected user.
                    </div>
                </fieldset>
            </section>

            <section class="p-5 space-y-3">
                <fieldset class="grid grid-cols-3">
                    <!-- Field 7: Email Template Body -->
                    <label class="font-medium mt-1">Personalise</label>
                    <select id="module-merge-fields" name="module-merge-fields" class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                        <option value="" selected disabled>Insert Module Field</option>
                        <!-- Options will be dynamically populated by jQuery -->
                    </select>
                    <select id="user-merge-fields" name="user-merge-fields" class="rounded-md py-2 px-3 ring-1 ring-gray-300 ml-3">
                        <option value="" selected disabled>Insert User Field</option>
                        <!-- Options will be dynamically populated by jQuery -->
                    </select>
                </fieldset>

                <!-- Field 8: Email Text -->
                <fieldset class="grid grid-cols-3">
                    <label for="email-body" class="font-medium mt-1">Email Content <span class="text-red-500">*</span></label>
                    <textarea id="email-body" name="email-body" required class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300" rows="10"></textarea>
                </fieldset>
                <fieldset id="email-error" class="grid grid-cols-3" style="margin-top: 0px !important; display: none;">
                    <label for="email-error" class="font-medium">&nbsp;</label>
                    <span id="email-error-text" class="col-span-2" style="font-size: 10px; color: red;"></span>
                </fieldset>

                <fieldset class="grid grid-cols-3">
                    <!-- Field 9: Add Opt Out toggle switch using Switchery -->
                    <label for="add-optout" class="font-medium mt-1">Allow Opt Out?</label>
                    <div class="col-span-2 relative" id="add-optout-tooltip">
                        <input type="checkbox" id="add-optout" name="add-optout" class="add-optout-toggle">
                        <div id="tooltip-default" role="tooltip" class="absolute z-10 invisible inline-block ml-2 px-2 py-0.5 text-sm text-white transition-opacity duration-300 bg-gray-700 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                            Switch on if sending marketing Email
                        </div>
                    </div>
                </fieldset>
            </section>

            <footer class="flex justify-between p-5">
                <div>
                    <div id="success-message" class="message success text-green-800"></div>
                    <div id="error-message" class="message error text-red-700"></div>
                </div>

                <!-- Submit Button -->
                <input type="button" value="Next" id="next-button" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer">
            </footer>
        </form>
    </div>

    <!-- Preview page -->
    <div id="preview-container" style="display: none" class="divide-y">

        <header class="p-5 flex space-x-5 items-center">
            <div class="shadow border rounded items-center flex px-3 py-2">
                <i class="fa-regular fa-comment-dots text-xl"></i>
            </div>
            <p class="text-gray-600">Preview the selected configurations and click "Send" to send or schedule the Email.</p>
        </header>

        <section class="summary-section p-5 grid grid-cols-3 space-y-3">
            <p class="summary-title font-medium">Summary</p>

            <div class="col-span-2 grid grid-cols-2 gap-4" style="margin-top: 0px !important;">
                <div class="recipient-title">
                    <p>Recipient Selected</p>
                    <p id="recipient-value" class="text-xl font-bold"> - </p>
                </div>

                <div class="credit-title">
                    <p>Estimated Credit</p>
                    <p id="credit-value" class="text-xl font-bold"> - </p>
                </div>

                <div class="schedule-title col-span-2">
                    <p>Schedule</p>
                    <p id="schedule-value" class="text-xl font-bold"></p>
                </div>
            </div>
        </section>

        <section class="campaign-template-section p-5 space-y-3">
            <div class="campaign-section">
                <div class="grid grid-cols-3">
                    <p class="campaign-title font-medium">Campaign Name</p>
                    <p id="campaign-value" class="col-span-2"></p>
                </div>
            </div>
            <div class="template-section">
                <div class="grid grid-cols-3">
                    <p class="template-title font-medium">Message Template</p>
                    <p id="template-value"></p>
                </div>
            </div>
        </section>

        <section class="message-section p-5 space-y-3">
            <div class="grid grid-cols-3">
                <p class="message-title font-medium">Message</p>
                <p id="message-value" class="col-span-2"></p>
            </div>
        </section>

        <section class="p-5 grid grid-cols-3" id="notices" style="display: none;">
            <p class="font-medium">Notifications</p>
            <div class="notices-group col-span-2 flex flex-col gap-2">
                <div class="notice-template flex gap-3 items-center" style="display: none">
                    <div class="notice-count rounded-md bg-green-50 px-1.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/50"></div>
                    <p class="notice-message"></p>
                </div>
            </div>
        </section>

        <footer class="button-section flex justify-between p-5">
            <input type="button" value="&larr; Back" id="back-button" class="rounded-md bg-gray-400 px-4 py-2 text-white hover:bg-gray-500 cursor-pointer">
            <input type="button" value="Send" id="send-button" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer">
            <input type="button" value="Close" id="close-button" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer" style="display: none;">
        </footer>
    </div>

    <div id="loader-overlay" style="display: none;" class="fixed inset-0 flex items-center bg-black opacity-50 justify-center rounded-lg">
        <i class="fa-solid fa-spinner animate-spin text-8xl text-white"></i>
    </div>

</body>
<style>
    /* Force editor to take full width and proper height */
    .ck-editor__top {
        width: 430px !important;
    }

    .ck-editor__main {
        width: 430px !important;
    }

    .ck-editor__editable_inline {
        width: 100% !important;
        min-height: 250px !important;
        box-sizing: border-box !important;
        resize: vertical;
    }

    /* Optional: override split layout if used */
    .ck.ck-reset.ck-editor.ck-rounded-corners {
        flex: 1 1 100% !important;
    }

    .ck-powered-by {
        display: none !important;
    }
</style>

</html>