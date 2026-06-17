<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("127.0.0.1", "aadhaar_test", "ftYI6.B#s2K&", "aadhaar_test");
if ($conn->connect_error) {
    echo "<div id='cards-data'><div class='date-cards'><div class='date-card'><div class='date'>DB Error</div><div class='count'>—</div></div></div></div>";
    echo "<table id='table-data' style='display:none'><tbody><tr><td colspan='5' style='text-align:center;'>Database connection failed. Please try again.</td></tr></tbody></table>";
    exit();
}

$fromDate = isset($_GET['from']) ? $conn->real_escape_string($_GET['from']) : null;
$toDate   = isset($_GET['to'])   ? $conn->real_escape_string($_GET['to'])   : null;
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
$summary = $conn->query($summarySql);
if ($summary && $summary->num_rows > 0) {
    while ($r = $summary->fetch_assoc()) {
        echo "<div class='date-card'>
                <div class='date'>" . date('d-m-Y', strtotime($r['system_date'])) . "</div>
                <div class='count'>{$r['total_count']}</div>
              </div>";
    }
} else {
    echo "<div class='date-card'><div class='date'>No Data</div><div class='count'>0</div></div>";
}
echo "</div></div>";

// Main table rows — user_id, user_count, macId, status, system_date
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
$data = $conn->query($dataSql);
echo "<table id='table-data' style='display:none'><tbody>";
if ($data && $data->num_rows > 0) {
    while ($row = $data->fetch_assoc()) {
        $statusClass = ($row['status'] === 'ACTIVE') ? 'status-active' : 'status-inactive';
        $dateFormatted = date('d-m-Y', strtotime($row['system_date']));
        $macName = htmlspecialchars($row['name'] ?? $row['macId']);
        echo "<tr class='match-row'>
                <td>{$row['user_id']}</td>
                <td><b>{$row['user_count']}</b></td>
                <td class='mac'>{$row['macId']}</td>
                <td>{$macName}</td>
                <td class='{$statusClass}'>{$row['status']}</td>
                <td>{$dateFormatted}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center;'>No Data Found</td></tr>";
}
echo "</tbody></table>";
$conn->close();
