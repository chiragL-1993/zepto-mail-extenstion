<body>

    <table id="email-history-table">
        <thead>
            <tr>
                <th>Email History Name</th>
                <th>Recipients</th>
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