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
.status-wrapper {
    padding: 15px;
}
.date-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.date-card {
    background: #fff;
    border-radius: 12px;
    padding: 14px 10px;
    text-align: center;
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    border-top: 5px solid #0ea5e9;
}
.date-card .date {
    font-size: 13px;
    color: #475569;
    font-weight: 600;
}
.date-card .count {
    font-size: 22px;
    font-weight: bold;
    color: #16a34a;
}
.table-box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    overflow-x: auto;
}
.status-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}
.status-table thead tr.group th {
    background: #0f172a;
    color: #fff;
    padding: 12px;
}
.status-table thead tr.header th {
    background: #f1f5f9;
    padding: 10px;
    border-bottom: 2px solid #cbd5e1;
}
.status-table td {
    padding: 9px;
    border-bottom: 1px solid #e5e7eb;
    text-align: center;
}
.match-row {
    background: #ecfdf3;
    border-left: 6px solid #16a34a;
}
.mac { font-weight: 600; color: #0f766e; }
.status-active  { color: #15803d; font-weight: bold; }
.status-inactive{ color: #b91c1c; font-weight: bold; }
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.download-btn {
    background: #16a34a;
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
}
.footer-note {
    margin-top: 10px;
    font-size: 12px;
    color: #64748b;
    text-align: right;
}
</style>

<script>
function loadData() {
    fetch("mac/status_report.php")
        .then(res => res.text())
        .then(html => {
            const temp = document.createElement("div");
            temp.innerHTML = html;

            const cards = temp.querySelector("#cards-data");
            document.getElementById("summaryArea").innerHTML =
                cards ? cards.innerHTML : "";

            const liveTbody = document.getElementById("liveData");
            liveTbody.innerHTML = "";

            const table = document.createElement("table");
            table.innerHTML = "<tbody>" + html + "</tbody>";

            const rows = table.querySelectorAll("tr");
            rows.forEach(row => {
                liveTbody.appendChild(row);
            });
        });
}

function exportToExcel() {
    let table = document.querySelector(".status-table");
    let html = table.outerHTML.replace(/ /g, '%20');
    let filename = 'matched_mac_report_' +
        new Date().toISOString().slice(0,10) + '.xls';
    let link = document.createElement("a");
    link.href = 'data:application/vnd.ms-excel,' + html;
    link.download = filename;
    link.click();
}

window.onload = loadData;
setInterval(loadData, 5000);
</script>

<div class="status-wrapper">
    <div class="top-bar">
        <h4>Live Matched MAC Address Status</h4>
        <button class="download-btn" onclick="exportToExcel()">&#11015; Download Excel</button>
    </div>

    <div id="summaryArea"></div>

    <div class="table-box">
        <table class="status-table">
            <thead>
                <tr class="group">
                    <th colspan="4">Live Matched MAC Report</th>
                </tr>
                <tr class="header">
                    <th>User ID</th>
                    <th>Count</th>
                    <th>MAC ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="liveData"></tbody>
        </table>
    </div>

    <div class="footer-note">
        &#128260; Auto refresh every 5 seconds | &#128994; Only matched MAC addresses shown
    </div>
</div>

            </div><!-- .animated -->
        </div><!-- /.content -->

<?php include('../layout/footer.php'); ?>
