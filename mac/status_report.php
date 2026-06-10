<?php
$conn = new mysqli("localhost", "aadhaar_test", "ftYI6.B#s2K&", "aadhaar_test");
if ($conn->connect_error) { die("DB Error"); }

// Date summary cards (last 7 days)
echo "<div id='cards-data'><div class='date-cards'>";
$summarySql = "
    SELECT s.system_date, SUM(s.user_count) AS total_count
    FROM system_name_data s
    INNER JOIN map m ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))
    GROUP BY s.system_date
    ORDER BY s.system_date DESC
    LIMIT 15
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

// Main table rows — only: user_id, user_count, macId, status
$dataSql = "
    SELECT s.user_id, s.user_count, s.macId, m.status
    FROM system_name_data s
    INNER JOIN map m ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))
    ORDER BY s.system_date DESC
";
$data = $conn->query($dataSql);
if ($data && $data->num_rows > 0) {
    while ($row = $data->fetch_assoc()) {
        $statusClass = ($row['status'] === 'ACTIVE') ? 'status-active' : 'status-inactive';
        echo "<tr class='match-row'>
                <td>{$row['user_id']}</td>
                <td><b>{$row['user_count']}</b></td>
                <td class='mac'>{$row['macId']}</td>
                <td class='{$statusClass}'>{$row['status']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>No Data Found</td></tr>";
}
$conn->close();
