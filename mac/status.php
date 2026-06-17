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
        <a id="excelLink" href="mac/status_excel.php" target="_blank" style="display:none;"></a>
    </div>

    <!-- Date Filter -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <label class="mb-0"><b>From:</b></label>
                    <input type="date" id="from_date" class="form-control form-control-sm d-inline-block" style="width:160px;">
                </div>
                <div class="col-auto">
                    <label class="mb-0"><b>To:</b></label>
                    <input type="date" id="to_date" class="form-control form-control-sm d-inline-block" style="width:160px;">
                </div>
                <div class="col-auto mt-1">
                    <button class="btn btn-sm btn-primary" onclick="applyFilter()">&#128269; Filter</button>
                    <button class="btn btn-sm btn-secondary ml-1" onclick="resetFilter()">&#10006; Reset</button>
                </div>
            </div>
        </div>
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
                        <th>Name</th>
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

function loadData(fromDate, toDate) {
    let url = "mac/status_report.php";
    if (fromDate && toDate) {
        url += "?from=" + fromDate + "&to=" + toDate;
    }
    fetch(url)
        .then(res => res.text())
        .then(html => {
            const temp = document.createElement("div");
            temp.innerHTML = html;

            // Date cards
            const cards = temp.querySelector("#cards-data");
            if (cards) document.getElementById("summaryArea").innerHTML = cards.outerHTML;

            // Table rows
            const tableData = temp.querySelector("#table-data tbody");
            if (tableData) {
                if (dtTable) {
                    dtTable.destroy();
                    dtTable = null;
                }
                document.getElementById("liveData").innerHTML = tableData.innerHTML;
                dtTable = $('#statusTable').DataTable({
                    pageLength: 25,
                    order: [[5, 'desc']],
                    language: {
                        search: "Search:",
                        emptyTable: "Koi data nahi mila",
                        zeroRecords: "Filter mein koi record nahi mila"
                    },
                    columnDefs: [{ targets: [1], className: 'text-center' }]
                });
            }
        })
        .catch(err => console.error("Fetch error:", err));
}

function applyFilter() {
    const from = document.getElementById("from_date").value;
    const to   = document.getElementById("to_date").value;
    if (!from || !to) { alert("Dono dates select karo!"); return; }
    if (from > to)    { alert("From date, To date se pehle honi chahiye!"); return; }
    clearInterval(autoRefresh);
    loadData(from, to);
}

function resetFilter() {
    document.getElementById("from_date").value = "";
    document.getElementById("to_date").value   = "";
    clearInterval(autoRefresh);
    loadData();
    autoRefresh = setInterval(loadData, 30000);
}

function exportToExcel() {
    const from = document.getElementById("from_date").value;
    const to   = document.getElementById("to_date").value;
    let url = "mac/status_excel.php";
    if (from && to) {
        url += "?from=" + from + "&to=" + to;
    }
    document.getElementById("excelLink").href = url;
    document.getElementById("excelLink").click();
}

var autoRefresh;
window.onload = function() {
    loadData();
    autoRefresh = setInterval(loadData, 30000);
};
</script>

            </div><!-- .animated -->
        </div><!-- /.content -->

<?php include('../layout/footer.php'); ?>
<!-- DataTables for statusTable -->
<link rel="stylesheet" href="assets/css/lib/datatable/dataTables.bootstrap.min.css">
<script src="assets/js/lib/data-table/datatables.min.js"></script>
<script src="assets/js/lib/data-table/dataTables.bootstrap.min.js"></script>
