<?php
require_once __DIR__ . '/../database.php';
$conn = $link;

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

