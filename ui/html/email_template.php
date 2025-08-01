<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create SMS Template</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://live.zwidgets.com/js-sdk/1.2/ZohoEmbededAppSDK.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/sms_template.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://unpkg.com/tailwindcss-cdn@3.3.4/tailwindcss.js"></script>
    <link rel="stylesheet" href="css/common.css" />
</head> -->

<body class="text-xs text-gray-800">

    <div id="error-container" style="display: none;" class="m-5 p-10 rounded border flex justify-between items-center divide-y">
        <div id="settings-error-message" class="message error">Please update your Zepto Email settings to begin sending messages.</div>
        <button type="button" id="open-settings" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer">Update Settings</button>
    </div>

    <div id="form-container">
        <form id="email-template-form" action="#" method="post" class="divide-y">
            <header class="p-5 flex space-x-5 items-center">
                <div class="shadow border rounded items-center flex px-3 py-2">
                    <i class="fa-regular fa-envelope text-xl"></i>
                </div>
                <p class="text-gray-600">Create a custom template by selecting your desired module; the corresponding fields for that module will be presented for use as merge fields.</p>
            </header>

            <section class="p-5 space-y-3">
                <fieldset class="grid grid-cols-3">
                    <!-- Field 1: Email Template Name -->
                    <label for="email-template-name" class="font-medium mt-1">Template Name <span class="text-red-600">*</span></label>
                    <input type="text" id="email-template-name" name="email-template-name" required class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300">
                </fieldset>

                <fieldset class="grid grid-cols-3">
                    <!-- Field 2: Module Name -->
                    <label for="module-name" class="font-medium">Module <span class="text-red-600">*</span></label>
                    <select id="module-name" name="module-name" required class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300">
                        <option value="" selected></option>
                        <!-- Options will be dynamically populated by jQuery -->
                    </select>
                </fieldset>
                <fieldset class="grid grid-cols-3">
                    <!-- Field 1: Email Template Subject -->
                    <label for="email-template-subject" class="font-medium mt-1">Email Template Subject <span class="text-red-600">*</span></label>
                    <input type="text" id="email-template-subject" name="email-template-subject" required class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300">
                </fieldset>
                <fieldset class="grid grid-cols-3">
                    <!-- Field 3: Email Template Body -->
                    <label for="email-template-body" class="font-medium">Email Template Body <span class="text-red-600">*</span></label>
                    <div class="grid grid-cols-2 col-span-2 gap-3">
                        <select id="module-merge-fields" name="module-merge-fields" class="rounded-md py-2 px-3 ring-1 ring-gray-300" placeholder="x">
                            <option value="" selected disabled>Insert Module Field</option>
                            <!-- Options will be dynamically populated by jQuery -->
                        </select>
                        <select id="user-merge-fields" name="user-merge-fields" class="rounded-md py-2 px-3 ring-1 ring-gray-300">
                            <option value="" selected disabled>Insert User Field</option>
                            <!-- Options will be dynamically populated by jQuery -->
                        </select>
                        <textarea id="email-template-body" name="email-template-body" required class="rounded-md py-2 px-3 ring-1 ring-gray-300 col-span-2" rows="10"></textarea>
                    </div>
                </fieldset>
                <fieldset id="email-error" class="grid grid-cols-3" style="margin-top: 0px !important; display: none;">
                    <label for="email-error" class="font-medium">&nbsp;</label>
                    <span id="email-error-text" class="col-span-2" style="font-size: 10px; color: red;"></span>
                </fieldset>

                <fieldset class="grid grid-cols-3">
                    <!-- Field 4: Active toggle switch using Switchery -->
                    <label for="status" class="font-medium mt-1">Status <span class="text-red-600">*</span></label>
                    <select id="status" name="status" class="col-span-2 rounded-md py-2 px-3 ring-1 ring-gray-300" required>
                        <option value="Active" selected>Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </fieldset>
            </section>

            <footer class="flex justify-between p-5">
                <div>
                    <div id="success-message" class="text-green-700" style="display: none;">Email Template saved successfully!</div>
                    <div id="error-message" class="text-red-600" style="display: none">Failed to save template. Please try again.</div>
                </div>

                <!-- Submit Button -->
                <input type="button" value="Save Template" id="save-template-button" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer">
            </footer>

        </form>
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