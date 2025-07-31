var scheduledEmailJobs = [];
var pageSize = 5;
var currentPage = 1;
var orgId;

$(document).ready(function () {

    // Subscribe to the EmbeddedApp onPageLoad event before initializing 
    ZOHO.embeddedApp.on('PageLoad', async function (data) {
        showLoader();

        // Get OrgInfo and push to hidden fields
        var orgData = await ZOHO.CRM.CONFIG.getOrgInfo();
        orgId = orgData.org[0].id;

        // Get saved Email Settings
        var savedData = await getSavedSettings(orgId);
        if (savedData.success && isSettingsPresent(savedData)) {
            // Initial population of table rows
            let response = await getScheduledEmailJobs(orgId);
            if (response.success) {
                scheduledEmailJobs = response.message;
                renderTableRows(null);
            }
        } else {
            // If Email Settings not present, show error
            hideLoader();
            $('#scheduled-email-table').hide();
            $('#page-settings').hide();
            $('#footer-note').hide();
            $('#error-container').show();
            $('#settings-error-message').show();
        }

        // Event handler for cancel button click
        $('#scheduled-email-table').on('click', '.cancel-button', function () {
            var rowId = $(this).data('row-id');
            var confirmationMessage = "Are you sure you want to cancel the schedeled Email Job?";
            showConfirmationDialog(confirmationMessage, async function (proceed) {
                if (proceed) {
                    // Remove the row on successful response
                    let response = await withdrawScheduledEmailJob(orgId, rowId);
                    if (response.success) {
                        removeJobById(rowId);
                        renderTableRows(null);
                    }
                }
            });
        });

        // Event handler for search input
        $('#search').on('input', function () {
            applySearch();
        });

        // Event handler for page size change
        $('#page-size').on('change', function () {
            pageSize = parseInt($(this).val());
            currentPage = 1; // Reset to the first page after changing page size
            applyPagination();
        });

        // Event handler for previous page button
        $('#prevPage').on('click', function () {
            if (currentPage > 1) {
                currentPage--;
                applyPagination();
            }
        });

        // Event handler for next page button
        $('#nextPage').on('click', function () {
            var totalPages = Math.ceil(scheduledEmailJobs.length / pageSize);
            if (currentPage < totalPages) {
                currentPage++;
                applyPagination();
            }
        });

        // Add event listener to display settings widget
        $('#open-settings').on('click', function () {
            openSettingsWebtab();
        });

        hideLoader();
    });

    // Initializing the widget.
    ZOHO.embeddedApp.init();
});

// Ajax request to get scheduled Email jobs from squirrel db
const getScheduledEmailJobs = async function (orgId) {
    return $.ajax({
        url: SQUIRREL_EXTENSION_PATH + GET_SCHEDULED_JOBS + '?orgid=' + orgId,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            console.log(response);
            // Process the response
            if (!response.success) {
                $('#page-settings').hide();
                showErrorMessage(response.message);
            }
        },
        error: function () {
            // Show error message in case of AJAX failure
            $('#page-settings').hide();
            showErrorMessage('Error retreiving scheduled email jobs. Please try again!');
        }
    });
}

// Ajax request to withdraw scheduled Email job from squirrel db
const withdrawScheduledEmailJob = async function (orgId, batchId) {
    let formData = { 'orgid': orgId, 'batch_id': batchId };
    return $.ajax({
        url: SQUIRREL_EXTENSION_PATH + WITHDRAW_SCHEDULED_JOB,
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
            return response;
        },
        error: function () {
            // Show error message in case of AJAX failure
            showErrorMessage('Error cancelling the job. Please try again!');
        },
        complete: function () {
            // Hide full-page loader after request completion
            hideLoader();
        }
    });
}

function updatePaginationButtons() {
    var totalPages = Math.ceil(scheduledEmailJobs.length / pageSize);
    var currentPageElement = $('#currentPage');
    currentPageElement.text(currentPage + ' of ' + totalPages);

    $('#prevPage').prop('disabled', currentPage === 1);
    $('#nextPage').prop('disabled', currentPage === totalPages);
}

function renderTableRows(data) {
    var tableBody = $('#table-body');
    tableBody.empty(); // Clear existing rows

    if (scheduledEmailJobs.length === 0) {
        $('#no-jobs-message').text("No Scheduled Email Jobs").show();
        $('#page-settings').hide();
    } else {
        $('#no-jobs-message').hide();

        var jobsToRender = data || scheduledEmailJobs;
        var startIndex = (currentPage - 1) * pageSize;
        var endIndex = startIndex + pageSize;

        $.each(jobsToRender.slice(startIndex, endIndex), function (index, job) {
            // Assign a unique hidden id to each row
            var rowId = job.id;

            // Append a row to the table
            tableBody.append(
                '<tr id="' + rowId + '" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">' +
                '<th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">' + job.scheduledTime + '</th>' +
                '<td class="px-6 py-4">' + job.module + '</td>' +
                '<td class="px-6 py-4">' + job.campaign_name + '</td>' +
                '<td class="px-6 py-4">' + job.recipients + '</td>' +
                '<td class="px-6 py-4">' + nl2br(job.emailText) + '</td>' +
                '<td class="px-6 py-4"><input type="button" value="Cancel" data-row-id="' + rowId + '" class="cancel-button rounded-md bg-[#54a4da] px-4 py-2 text-white hover:bg-sky-500 cursor-pointer"></td>' +
                '</tr>'
            );
        });

        updatePaginationButtons();
    }
}

function applyPagination() {
    // Calculate totalJobs and totalPages based on the current data set
    var totalJobs = $('#table-body tr').length;
    var totalPages = Math.ceil(totalJobs / pageSize);

    // Update pagination buttons based on the current data set
    updatePaginationButtons();

    // Render table rows based on the current page
    renderTableRows(null);
}

function removeJobById(id) {
    const index = scheduledEmailJobs.findIndex(item => item.id === id);

    if (index !== -1) {
        scheduledEmailJobs.splice(index, 1);
    }
}