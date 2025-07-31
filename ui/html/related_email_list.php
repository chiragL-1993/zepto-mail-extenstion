<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS History Related List</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://live.zwidgets.com/js-sdk/1.2/ZohoEmbededAppSDK.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/related_sms_list.js"></script>
    <link rel="stylesheet" href="css/related_sms_list.css">
</head>
<body> -->

<table id="sms-history-table">
    <thead>
        <tr>
            <th>SMS History Name</th>
            <th>Recipient Number</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <!-- Rows will be dynamically populated here -->
        <div id="loader-overlay" style="display: none;">
            <div id="loader">
                <img src="images/grey-spinner.gif" alt="Loading..." />
            </div>
        </div>
    </tbody>
</table>

<div class="pagination" id="pagination">
    <a href="#" id="prev-page">&lt;</a>
    <span id="page-range"></span>
    <a href="#" id="next-page">&gt;</a>
</div>

<div id="no-records" class="no-records">No Records Found</div>

</body>

</html>