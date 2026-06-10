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

$sql = "SELECT \n"

    . "    s.id AS system_id,\n"

    . "    s.user_id,\n"

    . "    s.user_name,\n"

    . "    s.user_count,\n"

    . "    s.system_date,\n"

    . "    s.macId,\n"

    . "    m.id AS map_id,\n"

    . "    m.name,\n"

    . "    m.macid,\n"

    . "    m.status\n"

    . "FROM system_name_data s\n"

    . "INNER JOIN map m \n"

    . "    ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))\n"

    . "ORDER BY s.system_date DESC";
    
$summary = $conn->query($sql);

$result = [];
while ($row = $summary->fetch_assoc()) {
    $result[] = $row;
}

header('Content-Type: application/json');
echo json_encode($result);

