var entityName, entityId;
var currentPage = 1;
var offset = 0;
var pageSize = 10;
var emailData = [];
var moreRecords = false;
var noRecords = true;

$(document).ready(async function () {

    // Subscribe to the EmbeddedApp onPageLoad event before initializing 
    ZOHO.embeddedApp.on('PageLoad', async function (data) {

        // Get the Entity Name and Entity ID from which the Custom Related List is initialized
        entityName = data.Entity;
        entityId = data.EntityId;

        // Initial population
        await populateRows();
        updatePagination();

        // Handle pagination for next page
        $('#prev-page').on('click', async function (event) {
            event.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                await populateRows();
                updatePagination();
            }
        });

        // Handle pagination for previous page
        $('#next-page').on('click', async function (event) {
            event.preventDefault();
            if (moreRecords) {
                currentPage++;
                await populateRows();
                updatePagination();
            }
        });
    });

    // Initializing the widget.
    ZOHO.embeddedApp.init();
});

async function populateRows() {
    showLoader();
    var tableBody = $('#email-history-table tbody');
    offset = (currentPage - 1) * pageSize;

    tableBody.empty(); // Clear existing rows

    var selectQuery = "select ";
    for (let index = 0; index < EMAIL_HISTORY_FIELDS.length; index++) {
        selectQuery += (index < EMAIL_HISTORY_FIELDS.length - 1) ? EMAIL_HISTORY_FIELDS[index] + ", " : EMAIL_HISTORY_FIELDS[index] + " ";
    }
    selectQuery += "from " + EMAIL_HISTORY_MODULE + " ";
    selectQuery += "where " + EMAIL_HISTORY_CM_Name_FIELD + " = '" + entityName + "' and " + EMAIL_HISTORY_CM_ID_FIELD + " = '" + entityId + "' ";
    selectQuery += "limit " + offset + ", " + pageSize;

    var config = {
        "select_query": selectQuery
    }
    coqlResponse = await ZOHO.CRM.API.coql(config);

    if (coqlResponse && coqlResponse.data && coqlResponse.data.length > 0) {
        noRecords = false
        emailData = coqlResponse.data;
        moreRecords = coqlResponse.info.more_records;
        $.each(emailData, function (index, email) {
            var row = $('<tr>');
            //$('<td>').text(email[EMAIL_HISTORY_FIELDS[0]]).appendTo(row);
            var emailHistoryNameCell = $('<td>');
            var link = $('<a>', {
                href: '#',
                text: email[EMAIL_HISTORY_FIELDS[1]],
                click: function () {
                    handleLinkClick(email[EMAIL_HISTORY_FIELDS[0]]);
                }
            });
            link.addClass('email-history-link');
            emailHistoryNameCell.append(link).appendTo(row);
            $('<td>').text(email[EMAIL_HISTORY_FIELDS[2]]).appendTo(row);
            $('<td>').text(email[EMAIL_HISTORY_FIELDS[3]]).appendTo(row);
            tableBody.append(row);
        });
    }

    // Show or hide "No Records Found" message
    var noRecordsMessage = $('#no-records');
    noRecordsMessage.css('display', noRecords ? 'block' : 'none');
    hideLoader();
}

function updatePagination() {
    var currentPageElement = $('#page-range');
    var prevPageButton = $('#prev-page');
    var nextPageButton = $('#next-page');

    if (noRecords) {
        prevPageButton.css('display', 'none');
        nextPageButton.css('display', 'none');

        currentPageElement.text('');
    } else {
        prevPageButton.css('display', 'inline-block');
        prevPageButton.removeClass('disabled-link');
        nextPageButton.css('display', 'inline-block');
        nextPageButton.removeClass('disabled-link');

        currentPageElement.text((currentPage - 1) * pageSize + 1 + ' - ' + (offset + emailData.length));
        currentPageElement.text((currentPage - 1) * pageSize + 1 + '\u00A0\u00A0 - \u00A0\u00A0' + (offset + emailData.length));
    }

    // Disable previous page icon on first page
    if (currentPage == 1) {
        prevPageButton.addClass('disabled-link');
    }
    // Disable next page icon on last page
    if (!moreRecords) {
        nextPageButton.addClass('disabled-link')
    }
}

function handleLinkClick(recordId) {
    // Open Email History record link
    ZOHO.CRM.UI.Record.open({ Entity: EMAIL_HISTORY_MODULE, RecordID: recordId });
}