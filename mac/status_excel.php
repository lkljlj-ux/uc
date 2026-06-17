<?php
session_start();
include(__DIR__ . '/../database.php');
if(!isset($_SESSION['user_token'])){
    header("location:/login.php");
    exit();
}

$fromDate = (isset($_GET['from']) && $_GET['from']) ? mysqli_real_escape_string($link, $_GET['from']) : '';
$toDate   = (isset($_GET['to'])   && $_GET['to'])   ? mysqli_real_escape_string($link, $_GET['to'])   : '';

$dateWhere = '';
if($fromDate && $toDate){
    $dateWhere = "AND s.system_date BETWEEN '$fromDate' AND '$toDate'";
} 

$filename = 'mac_report';
if($fromDate && $toDate) $filename .= '_' . $fromDate . '_to_' . $toDate;
else $filename .= '_' . date('Y-m-d');

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
header('Pragma: no-cache');
echo "\xEF\xBB\xBF";

$sql = "
    SELECT s.user_id, s.user_count, s.macId, m.name, m.status, s.system_date
    FROM system_name_data s
    INNER JOIN map m ON TRIM(UPPER(s.macId)) = TRIM(UPPER(m.macid))
    WHERE 1=1 $dateWhere
    ORDER BY s.system_date DESC, s.macId ASC
";
$result = mysqli_query($link, $sql);
?>
<table border="1">
    <thead>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <th>#</th>
            <th>Date</th>
            <th>MAC ID</th>
            <th>Name</th>
            <th>User ID</th>
            <th>Count</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $i = 1;
    if($result && mysqli_num_rows($result) > 0):
        while($row = mysqli_fetch_assoc($result)):
            $statusColor = ($row['status'] === 'ACTIVE') ? '#166534' : '#991b1b';
    ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= date('d-m-Y', strtotime($row['system_date'])) ?></td>
            <td><?= htmlspecialchars($row['macId']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['user_id']) ?></td>
            <td align="center"><b><?= htmlspecialchars($row['user_count']) ?></b></td>
            <td style="color:<?= $statusColor ?>;font-weight:bold;"><?= htmlspecialchars($row['status']) ?></td>
        </tr>
    <?php endwhile; else: ?>
        <tr><td colspan="7" align="center">No Data Found</td></tr>
    <?php endif; ?>
    </tbody>
</table>
