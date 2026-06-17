<?php
mysqli_report(MYSQLI_REPORT_OFF);
include(__DIR__ . '/../database.php');

$fromDate = isset($_GET['from']) ? mysqli_real_escape_string($link, $_GET['from']) : null;
$toDate   = isset($_GET['to'])   ? mysqli_real_escape_string($link, $_GET['to'])   : null;
$hasFilter = $fromDate && $toDate;

// Date summary cards (last 7 days)
echo "<div id='cards-data'><div class='date-cards'>";
$summarySql = "
    SELECT s.system_date, SUM(s.user_count) AS total_count
    FROM system_name_data s
    INNER JOIN map m ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))
    GROUP BY s.system_date
    ORDER BY s.system_date DESC
    LIMIT 7
";
$summary = mysqli_query($link, $summarySql);
if ($summary && mysqli_num_rows($summary) > 0) {
    while ($r = mysqli_fetch_assoc($summary)) {
        echo "<div class='date-card'>
                <div class='date'>" . date('d-m-Y', strtotime($r['system_date'])) . "</div>
                <div class='count'>{$r['total_count']}</div>
              </div>";
    }
} else {
    echo "<div class='date-card'><div class='date'>No Data</div><div class='count'>0</div></div>";
}
echo "</div></div>";

// Main table rows
$dateWhere = $hasFilter
    ? "AND s.system_date BETWEEN '$fromDate' AND '$toDate'"
    : "";

$dataSql = "
    SELECT s.user_id, s.user_count, s.macId, m.name, m.status, s.system_date
    FROM system_name_data s
    INNER JOIN map m ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))
    WHERE 1=1 $dateWhere
    ORDER BY s.system_date DESC
";
$data = mysqli_query($link, $dataSql);
echo "<table id='table-data' style='display:none'><tbody>";
if ($data && mysqli_num_rows($data) > 0) {
    while ($row = mysqli_fetch_assoc($data)) {
        $statusClass   = ($row['status'] === 'ACTIVE') ? 'status-active' : 'status-inactive';
        $dateFormatted = date('d-m-Y', strtotime($row['system_date']));
        $macName       = htmlspecialchars($row['name'] ?? $row['macId']);
        echo "<tr class='match-row'>
                <td>{$row['user_id']}</td>
                <td><b>{$row['user_count']}</b></td>
                <td class='mac'>{$row['macId']}</td>
                <td>{$macName}</td>
                <td class='{$statusClass}'>{$row['status']}</td>
                <td>{$dateFormatted}</td>
              </tr>";
    }
}
echo "</tbody></table>";
