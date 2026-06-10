<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// nirmal singh self code 
$conn = new mysqli(
    "localhost",
    "aadhaar_test",
    "ftYI6.B#s2K&",
    "aadhaar_test"
);

if ($conn->connect_error) {
    die("DB Error");
}


echo "<div id='cards-data'>";
echo "<div class='date-cards'>";

// nirmal singh self code 
$summarySql = "
SELECT 
    s.system_date,
    SUM(s.user_count) AS total_count
FROM system_name_data s
INNER JOIN map m 
    ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))
WHERE s.system_date >= CURDATE() - INTERVAL 7 DAY
GROUP BY s.system_date
ORDER BY s.system_date DESC
";

$summary = $conn->query($summarySql);

if ($summary && $summary->num_rows > 0) {
    while ($r = $summary->fetch_assoc()) {
        echo "
        <div class='date-card'>
            <div class='date'>" . date('d-m-Y', strtotime($r['system_date'])) . "</div>
            <div class='count'>{$r['total_count']}</div>
        </div>";
    }
} else {
    echo "
    <div class='date-card'>
        <div class='date'>No Data</div>
        <div class='count'>0</div>
    </div>";
}

echo "</div>";
echo "</div>";


// nirmal singh self code 
$dataSql = "
SELECT 
    s.id AS system_id,
    s.user_id,
    s.user_name,
    s.user_count,
    s.system_date,
    s.macId,
    m.id AS map_id,
    m.name,
    m.macid,
    m.status
FROM system_name_data s
INNER JOIN map m 
    ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))
ORDER BY s.system_date DESC
";

$data = $conn->query($dataSql);

if ($data && $data->num_rows > 0) {
    while ($row = $data->fetch_assoc()) {

        $statusClass = ($row['status'] === 'ACTIVE')
            ? 'status-active'
            : 'status-inactive';

        echo "
        <tr class='match-row'>
            <td>{$row['system_id']}</td>
            <td>{$row['user_id']}</td>
            <td>{$row['user_name']}</td>
            <td><b>{$row['user_count']}</b></td>
            <td>" . date('d-m-Y', strtotime($row['system_date'])) . "</td>
            <td class='mac'>{$row['macId']}</td>

            <td>{$row['map_id']}</td>
            <td>{$row['name']}</td>
            <td class='mac'>{$row['macid']}</td>
            <td class='{$statusClass}'>{$row['status']}</td>
        </tr>";
    }
} else {
    echo "
    <tr>
        <td colspan='10' style='text-align:center;'>No Data Found</td>
    </tr>";
}

$conn->close();
