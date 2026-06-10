<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MAC Match Live Status</title>
<!-- nirmal singh self code -->

<style>
body {
    margin: 0;
    background: #f1f5f9;
    font-family: "Segoe UI", Arial, sans-serif;
    color: #1f2937;
}

.wrapper {
    max-width: 1600px;
    margin: 20px auto;
    padding: 15px;
}

h2 {
    text-align: center;
    margin-bottom: 15px;
}

/* DATE CARDS */
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

/* TABLE */
.table-box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1300px;
}

thead tr.group th {
    background: #0f172a;
    color: #fff;
    padding: 12px;
}

thead tr.header th {
    background: #f1f5f9;
    padding: 10px;
    border-bottom: 2px solid #cbd5e1;
}

td {
    padding: 9px;
    border-bottom: 1px solid #e5e7eb;
    text-align: center;
}

.match-row {
    background: #ecfdf3;
    border-left: 6px solid #16a34a;
}

.mac {
    font-weight: 600;
    color: #0f766e;
}

.status-active {
    color: #15803d;
    font-weight: bold;
}

.status-inactive {
    color: #b91c1c;
    font-weight: bold;
}

.top-bar {
    display: flex;
    justify-content: flex-end;
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
    fetch("status_report.php")
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

window.onload = loadData;
setInterval(loadData, 5000);
</script>



<script>

function exportToExcel() {
    let table = document.querySelector("table");
    let html = table.outerHTML.replace(/ /g, '%20');
    let filename = 'matched_mac_report_' +
        new Date().toISOString().slice(0,10) + '.xls';

    let link = document.createElement("a");
    link.href = 'data:application/vnd.ms-excel,' + html;
    link.download = filename;
    link.click();
}
</script>
</head>

<body>

<div class="wrapper">

    <h2>Live Matched MAC Address Status</h2>

    <div id="summaryArea"></div>

    <div class="top-bar">
        <button class="download-btn" onclick="exportToExcel()">⬇ Download Excel</button>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr class="group">
                    <th colspan="6">SYSTEM NAME DATA (TABLE-1)</th>
                    <th colspan="4">MAP DATA (TABLE-2)</th>
                </tr>
                <tr class="header">
                    <th>ID</th>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Count</th>
                    <th>Date</th>
                    <th>MAC ID</th>
                    <th>Map ID</th>
                    <th>Name</th>
                    <th>MAC ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="liveData"></tbody>
        </table>
    </div>

    <!-- nirmal singh self code -->
    <div class="footer-note">
        🔄 Auto refresh every 5 seconds | 🟢 Only matched MAC addresses shown 
    </div>

</div>

</body>
</html>
