<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled SMS Jobs</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://live.zwidgets.com/js-sdk/1.2/ZohoEmbededAppSDK.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/sms_jobs.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://unpkg.com/tailwindcss-cdn@3.3.4/tailwindcss.js"></script>
    <link rel="stylesheet" href="css/common.css" />
</head> -->

<body class="flex justify-center py-10 text-xs text-gray-800 bg-gray-100">

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg divide-y shadow-2xl w-[80%] rounded-lg bg-white">

        <div id="error-container" style="display: none;" class="m-5 p-10 rounded border flex justify-between items-center divide-y">
            <div id="settings-error-message" class="message error">Please update your Zepto Email settings to begin sending messages.</div>
            <button type="button" id="open-settings" class="rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer">Update Settings</button>
        </div>

        <table id="scheduled-email-table" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 w-[15%]">
                        Scheduled Time
                    </th>
                    <th scope="col" class="px-6 py-3 w-[15%]">
                        Module
                    </th>
                    <th scope="col" class="px-6 py-3 w-[20%]">
                        Campaign Name
                    </th>
                    <th scope="col" class="px-6 py-3 w-[5%]">
                        Recipients
                    </th>
                    <th scope="col" class="px-6 py-3 w-[40%]">
                        Email Body
                    </th>
                    <th scope="col" class="px-6 py-3 w-[5%]">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody id="table-body">
                <!-- Table rows will be dynamically populated here -->
            </tbody>

        </table>

        <div id="no-jobs-message" class="py-4 px-6 text-center font-medium text-gray-600 whitespace-nowrap dark:text-white" style="display: none"></div>
        <div id="success-message" class="py-4 px-6 text-center font-medium text-green-700 whitespace-nowrap dark:text-white" style="display: none"></div>
        <div id="error-message" class="py-4 px-6 text-center font-medium text-red-500 whitespace-nowrap dark:text-white" style="display: none"></div>

        <div id="page-settings" class="flex items-center justify-between px-6 py-3 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <div id="pagination" class="py-4 flex items-center space-x-4">
                <span class="text-sm">Page:</span>
                <button id="prevPage" class="px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300 cursor-pointer" disabled>&lt;</button>
                <span id="currentPage" class="text-sm font-medium">1</span>
                <button id="nextPage" class="px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300 cursor-pointer" disabled>&gt;</button>
            </div>
            <div id="page-size-select" class="flex items-center space-x-4">
                <label for="page-size" class="text-sm">Page Size:</label>
                <select id="page-size" class="rounded-md border px-2 py-1">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
        </div>

        <footer id="footer-note" class="flex justify-between p-5">
            <div>
                <div class="text-gray-400">Note: Jobs scheduled from the Email widget will take around 5-10mins to appear here after they have been scheduled.
                    Job can only be cancelled before 15mins of the scheduled time, after that the job will no longer appear in this list and can't be cancelled.
                </div>
            </div>
        </footer>

        <div id="loader-overlay" style="display: none;" class="fixed inset-0 flex items-center bg-black opacity-50 justify-center rounded-lg">
            <i class="fa-solid fa-spinner animate-spin text-8xl text-white"></i>
        </div>

    </div>

</body>

</html>