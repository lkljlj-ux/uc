<?php
include('../layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:/login.php");
    exit();
}
?>
        <!-- Content -->
        <div class="content" style="min-height: 610px;">
            <div class="animated fadeIn">

<style>
.status-wrapper { padding: 15px; }
.date-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}
.date-card {
    background: #fff;
    border-radius: 10px;
    padding: 12px 18px;
    text-align: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    border-top: 4px solid #0ea5e9;
    min-width: 110px;
}
.date-card .date { font-size: 12px; color: #475569; font-weight: 600; }
.date-card .count { font-size: 20px; font-weight: bold; color: #16a34a; }
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.download-btn {
    background: #16a34a; color: #fff;
    border: none; padding: 8px 14px;
    border-radius: 6px; cursor: pointer;
    font-size: 13px;
}
.download-btn:hover { background: #15803d; }
.status-active   { color: #15803d; font-weight: bold; }
.status-inactive { color: #b91c1c; font-weight: bold; }
</style>

<div class="status-wrapper">

    <div class="top-bar">
        <h4 style="margin:0;">Live Matched MAC Address Status</h4>
        <button class="download-btn" onclick="exportToExcel()">&#11015; Download Excel</button>
    </div>

    <!-- Date Summary Cards -->
    <div id="summaryArea"></div>

    <!-- Main Table -->
    <div class="card">
        <div class="card-header"><strong class="card-title">Live Matched MAC Report</strong></div>
        <div class="card-body">
            <table id="statusTable" class="table table-bordered table-striped table-hover" style="width:100%">
                <thead class="thead-dark">
                    <tr>
                        <th>User ID</th>
                        <th>Count</th>
                        <th>MAC ID</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="liveData"></tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:8px; font-size:12px; color:#64748b; text-align:right;">
        &#128260; Auto refresh every 5 seconds &nbsp;|&nbsp; &#128994; Only matched MAC addresses shown
    </div>
</div>

<script>
var dtTable = null;

function loadData() {
    fetch("mac/status_report.php")
        .then(res => res.text())
        .then(html => {
            const temp = document.createElement("div");
            temp.innerHTML = html;

            // Date cards
            const cards = temp.querySelector("#cards-data");
            if (cards) document.getElementById("summaryArea").innerHTML = cards.outerHTML;

            // Table rows
            const tableData = temp.querySelector("#table-data");
            if (tableData) {
                if (dtTable) {
                    dtTable.destroy();
                    dtTable = null;
                }
                document.getElementById("liveData").innerHTML = tableData.innerHTML;
                dtTable = $('#statusTable').DataTable({
                    pageLength: 25,
                    order: [[4, 'desc']],
                    language: { search: "Search:" },
                    columnDefs: [{ targets: [1], className: 'text-center' }]
                });
            }
        })
        .catch(err => console.error("Fetch error:", err));
}

function exportToExcel() {
    let table = document.getElementById("statusTable");
    let html = table.outerHTML.replace(/ /g, '%20');
    let filename = 'mac_report_' + new Date().toISOString().slice(0,10) + '.xls';
    let link = document.createElement("a");
    link.href = 'data:application/vnd.ms-excel,' + html;
    link.download = filename;
    link.click();
}

window.onload = function() {
    loadData();
    setInterval(loadData, 5000);
};
</script>

            </div><!-- .animated -->
        </div><!-- /.content -->

<?php include('../layout/footer.php'); ?>
