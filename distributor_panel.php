<?php
ob_start();
session_start();
if(!isset($_SESSION['dist_id'])){
    header("location:distributor_login.php");
    exit();
}
include('database.php');

$dist_id   = (int)$_SESSION['dist_id'];
$dist_name = htmlspecialchars($_SESSION['dist_name']);
$today     = date('Y-m-d');

$dist_row  = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM distributors WHERE id=$dist_id"));
$svc_list  = !empty($dist_row['services']) ? explode(',', $dist_row['services']) : [];

$all_services = [
    'operator_add'  => ['label'=>'Operator Add',  'icon'=>'fa-user-plus',    'color'=>'#3498db'],
    'xml_upload'    => ['label'=>'XML Upload',     'icon'=>'fa-file-code-o', 'color'=>'#27ae60'],
    'map_machine'   => ['label'=>'Map Machine',    'icon'=>'fa-map-marker',  'color'=>'#f39c12'],
    'mac_coupon'    => ['label'=>'MAC Coupon',     'icon'=>'fa-ticket',      'color'=>'#e74c3c'],
    'all_report'    => ['label'=>'All Report',     'icon'=>'fa-bar-chart',   'color'=>'#1abc9c'],
];

$macs_q = mysqli_query($link, "
    SELECT m.name, m.macid, m.status,
           COALESCE(cnt.c, 0) AS today_count
    FROM distributor_macs dm
    JOIN map m ON m.macid = dm.macid
    LEFT JOIN (
        SELECT macId, COUNT(*) as c
        FROM system_name_data
        WHERE DATE(system_date) = '$today'
        GROUP BY macId
    ) cnt ON cnt.macId = m.macid
    WHERE dm.distributor_id = $dist_id
    ORDER BY m.name ASC
");

$total_macs   = 0;
$active_macs  = 0;
$today_total  = 0;
$rows_cache   = [];
while($r = mysqli_fetch_assoc($macs_q)){
    $total_macs++;
    if($r['status'] === 'ACTIVE') $active_macs++;
    $today_total += $r['today_count'];
    $rows_cache[] = $r;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Distributor Panel — <?= $dist_name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link href='https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700' rel='stylesheet'>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
        }

        .top-bar {
            background: linear-gradient(135deg, #0d1b2a, #0a3d62);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
        }
        .top-bar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .top-bar .brand .icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #1abc9c, #16a085);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .top-bar .brand .icon i { color: #fff; font-size: 18px; }
        .top-bar .brand h1 {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        .top-bar .brand small {
            color: rgba(255,255,255,0.5);
            font-size: 11px;
            display: block;
            margin-top: -2px;
        }
        .top-bar .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .top-bar .user-info .uname {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
        }
        .top-bar .user-info .uname strong { color: #1abc9c; }
        .btn-logout {
            background: rgba(231,76,60,0.2);
            border: 1px solid rgba(231,76,60,0.4);
            color: #e74c3c;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background: rgba(231,76,60,0.35);
            color: #e74c3c;
            text-decoration: none;
        }

        .main-content { padding: 28px; }

        .stat-card {
            border-radius: 16px;
            padding: 20px 24px;
            color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .stat-card h2 { font-size: 32px; font-weight: 700; margin: 0; }
        .stat-card p  { margin: 0; font-size: 13px; opacity: 0.85; margin-top: 4px; }
        .stat-card i  { font-size: 28px; opacity: 0.4; float: right; margin-top: -8px; }
        .bg-teal      { background: linear-gradient(135deg, #1abc9c, #16a085); }
        .bg-blue      { background: linear-gradient(135deg, #3498db, #2980b9); }
        .bg-orange    { background: linear-gradient(135deg, #f39c12, #d68910); }
        .bg-red       { background: linear-gradient(135deg, #e74c3c, #c0392b); }

        .card-panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .card-panel .cp-header {
            background: linear-gradient(135deg, #0d1b2a, #1b2838);
            padding: 16px 22px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-panel .cp-header h5 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }
        .card-panel .cp-body { padding: 0; }

        .mac-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .mac-table thead th {
            background: #f8f9fa;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid #e9ecef;
        }
        .mac-table tbody td {
            padding: 13px 16px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .mac-table tbody tr:hover { background: #f8fffe; }
        .mac-table tbody tr:last-child td { border-bottom: none; }

        .badge-active   { background: #d4edda; color: #155724; border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }
        .badge-inactive { background: #f8d7da; color: #721c24; border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }

        .today-count {
            font-size: 16px;
            font-weight: 700;
            color: #0a3d62;
        }
        .today-count.zero { color: #aaa; }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #aaa;
        }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }

        .today-badge {
            background: linear-gradient(135deg, #1abc9c, #16a085);
            color: #fff;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .svc-section { margin-top: 24px; }
        .svc-card {
            border-radius: 14px;
            padding: 20px 16px;
            text-align: center;
            color: #fff;
            margin-bottom: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .svc-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        }
        .svc-card i { font-size: 28px; margin-bottom: 8px; display: block; opacity: 0.9; }
        .svc-card span { font-size: 13px; font-weight: 600; display: block; }

        @media(max-width: 576px){
            .top-bar { flex-direction: column; gap: 12px; text-align: center; }
            .main-content { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="brand">
        <div class="icon"><i class="fa fa-sitemap"></i></div>
        <div>
            <h1>Distributor Portal</h1>
            <small>Aadhaar Station Management</small>
        </div>
    </div>
    <div class="user-info">
        <span class="uname"><i class="fa fa-user-circle"></i> <strong><?= $dist_name ?></strong></span>
        <a href="distributor_logout.php" class="btn-logout">
            <i class="fa fa-sign-out"></i> Logout
        </a>
    </div>
</div>

<!-- Main -->
<div class="main-content">

    <!-- Stat Cards -->
    <div class="row">
        <div class="col-6 col-md-3">
            <div class="stat-card bg-teal">
                <i class="fa fa-desktop"></i>
                <h2><?= $total_macs ?></h2>
                <p>Total MACs</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-blue">
                <i class="fa fa-check-circle"></i>
                <h2><?= $active_macs ?></h2>
                <p>Active MACs</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-red">
                <i class="fa fa-times-circle"></i>
                <h2><?= $total_macs - $active_macs ?></h2>
                <p>Inactive MACs</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-orange">
                <i class="fa fa-calendar"></i>
                <h2><?= $today_total ?></h2>
                <p>Aaj ki Registrations</p>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <?php if(!empty($svc_list)): ?>
    <div class="svc-section">
        <div class="card-panel mb-3">
            <div class="cp-header">
                <h5><i class="fa fa-cubes"></i> &nbsp;Aapki Assigned Services</h5>
                <span class="badge" style="background:rgba(255,255,255,0.15);color:#fff;font-size:12px;"><?= count($svc_list) ?> Services</span>
            </div>
            <div class="cp-body" style="padding:20px;">
                <div class="row">
                <?php foreach($all_services as $key => $svc): if(!in_array($key, $svc_list)) continue; ?>
                    <div class="col-6 col-md-2 col-sm-4">
                        <div class="svc-card" style="background:<?= $svc['color'] ?>;">
                            <i class="fa <?= $svc['icon'] ?>"></i>
                            <span><?= $svc['label'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MAC Table -->
    <div class="card-panel">
        <div class="cp-header">
            <h5><i class="fa fa-list"></i> &nbsp;Aapke Assigned MAC Machines</h5>
            <span class="today-badge"><i class="fa fa-calendar"></i> <?= date('d M Y') ?></span>
        </div>
        <div class="cp-body">
            <?php if(count($rows_cache) === 0): ?>
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <p>Abhi koi MAC machine assign nahi ki gayi hai.<br>Admin se contact karein.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Machine Name</th>
                        <th>MAC ID</th>
                        <th>Status</th>
                        <th>Aaj ki Count</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($rows_cache as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($r['macid']) ?></code></td>
                        <td>
                            <?php if($r['status'] === 'ACTIVE'): ?>
                                <span class="badge-active"><i class="fa fa-circle" style="font-size:8px;"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-inactive"><i class="fa fa-circle" style="font-size:8px;"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="today-count <?= $r['today_count'] == 0 ? 'zero' : '' ?>">
                                <?= $r['today_count'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
