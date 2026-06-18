<?php
// Excel download must run before layout/header.php emits any HTML
if(isset($_GET['excel'])){
    session_start();
    include(__DIR__ . '/database.php');
    if(!isset($_SESSION['user_token']) || $_SESSION['user_type'] != 'admin'){
        header("location:login.php");
        exit();
    }

    // Filter params
    $xdate_from = (isset($_GET['xdate_from']) && $_GET['xdate_from']) ? $_GET['xdate_from'] : date('Y-m-d');
    $xdate_to   = (isset($_GET['xdate_to'])   && $_GET['xdate_to'])   ? $_GET['xdate_to']   : date('Y-m-d');
    $xstatus    = isset($_GET['xstatus']) ? trim($_GET['xstatus']) : '';

    // Sanitize dates
    $xdate_from = date('Y-m-d', strtotime($xdate_from));
    $xdate_to   = date('Y-m-d', strtotime($xdate_to));
    $xdate_from_e = mysqli_real_escape_string($link, $xdate_from);
    $xdate_to_e   = mysqli_real_escape_string($link, $xdate_to);

    $is_today_only = ($xdate_from === date('Y-m-d') && $xdate_to === date('Y-m-d'));

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Pragma: no-cache');
    echo "\xEF\xBB\xBF";

    if(!$is_today_only){
        // Historical export from mac_coupon_logs
        header('Content-Disposition: attachment; filename="mac_coupon_report_' . $xdate_from . '_to_' . $xdate_to . '.xls"');
        $xwhere = "WHERE log_date BETWEEN '$xdate_from_e' AND '$xdate_to_e'";
        $xq = mysqli_query($link, "
            SELECT macid, mac_name, daily_limit, used_count, log_date
            FROM mac_coupon_logs
            $xwhere
            ORDER BY log_date DESC, macid ASC
        ");
        echo "MAC ID\tName\tDaily Limit\tUsed Count\tDate\n";
        while($xr = mysqli_fetch_assoc($xq)){
            echo $xr['macid'] . "\t"
               . $xr['mac_name'] . "\t"
               . $xr['daily_limit'] . "\t"
               . $xr['used_count'] . "\t"
               . $xr['log_date'] . "\n";
        }
    } else {
        // Live today's data with optional status filter
        header('Content-Disposition: attachment; filename="mac_coupon_report_' . date('Y-m-d') . '.xls"');
        $xwhere = 'WHERE 1=1';
        if($xstatus === 'active'){
            $xwhere .= " AND m.status='ACTIVE'";
        } elseif($xstatus === 'coupon_khatam'){
            $xwhere .= " AND mc.deactivated_by_coupon=1";
        } elseif($xstatus === 'inactive'){
            $xwhere .= " AND m.status='INACTIVE' AND (mc.deactivated_by_coupon IS NULL OR mc.deactivated_by_coupon=0)";
        } elseif($xstatus === 'no_coupon'){
            $xwhere .= " AND mc.macid IS NULL";
        }
        $xq = mysqli_query($link, "
            SELECT
                m.macid, m.name, m.status,
                mc.daily_limit, mc.deactivated_by_coupon,
                (SELECT COALESCE(SUM(s.user_count),0) FROM system_name_data s WHERE LOWER(TRIM(s.macId)) = LOWER(TRIM(m.macid)) AND s.system_date = CURDATE()) as today_count
            FROM map m
            LEFT JOIN mac_coupons mc ON LOWER(TRIM(m.macid)) = LOWER(mc.macid)
            $xwhere
            ORDER BY mc.daily_limit DESC, m.name ASC
        ");
        echo "MAC ID\tName\tDaily Limit\tToday Used\tRemaining\tStatus\tDate\n";
        while($xr = mysqli_fetch_assoc($xq)){
            $has_coupon  = !is_null($xr['daily_limit']);
            $limit       = (int)($xr['daily_limit'] ?? 0);
            $used        = (int)$xr['today_count'];
            $remaining   = $has_coupon ? max(0, $limit - $used) : '-';
            $daily_lim   = $has_coupon ? $limit : '-';
            if($xr['status'] === 'ACTIVE')          $status_label = 'Active';
            elseif($xr['deactivated_by_coupon'])     $status_label = 'Coupon Khatam';
            else                                     $status_label = 'Inactive';
            echo $xr['macid'] . "\t"
               . $xr['name'] . "\t"
               . $daily_lim . "\t"
               . $used . "\t"
               . $remaining . "\t"
               . $status_label . "\t"
               . date('Y-m-d') . "\n";
        }
    }
    exit();
}

include('layout/header.php');
if(!isset($_SESSION['user_token']) || $_SESSION['user_type'] != 'admin'){
    header("location:login.php");
    exit();
}

$success = '';
$error   = '';

// ─── AUTO-CHECK: Coupon status update on every page load ─────────────────────
// 1. Jinke mac_coupons set hain, unka aaj ka count check karo
$coupon_rows = mysqli_query($link, "SELECT macid, daily_limit, deactivated_by_coupon FROM mac_coupons");
while($cr = mysqli_fetch_assoc($coupon_rows)){
    $mac = mysqli_real_escape_string($link, $cr['macid']);
    $limit = (int)$cr['daily_limit'];

    // Aaj ka count from system_name_data table
    $cnt_res = mysqli_query($link, "SELECT COALESCE(SUM(user_count),0) as c FROM system_name_data WHERE LOWER(TRIM(macId))=LOWER('$mac') AND system_date=CURDATE()");
    $today_count = (int)mysqli_fetch_assoc($cnt_res)['c'];

    // Current map status fetch karo — decision ke liye
    $map_now = mysqli_fetch_assoc(mysqli_query($link, "SELECT status FROM map WHERE TRIM(macid)='$mac'"));
    $cur_status = $map_now ? $map_now['status'] : '';

    if($today_count >= $limit && $limit > 0){
        // Sirf tabhi act karo jab machine abhi ACTIVE hai (manually INACTIVE ko touch mat karo)
        if($cur_status === 'ACTIVE'){
            // ACTIVE → INACTIVE transition by coupon
            mysqli_query($link, "UPDATE map SET status='INACTIVE' WHERE TRIM(macid)='$mac'");
            mysqli_query($link, "UPDATE mac_coupons SET deactivated_by_coupon=1 WHERE macid='$mac'");
            // Log entry — ek din mein sirf ek entry (INSERT IGNORE via unique key simulation)
            $mac_name_r = mysqli_fetch_assoc(mysqli_query($link, "SELECT name FROM map WHERE TRIM(macid)='$mac'"));
            $mac_name_e = mysqli_real_escape_string($link, $mac_name_r ? $mac_name_r['name'] : '');
            $log_chk = mysqli_query($link, "SELECT id FROM mac_coupon_logs WHERE macid='$mac' AND log_date=CURDATE()");
            if(mysqli_num_rows($log_chk) == 0){
                mysqli_query($link, "INSERT INTO mac_coupon_logs (macid, mac_name, daily_limit, used_count, log_date) VALUES ('$mac','$mac_name_e',$limit,$today_count,CURDATE())");
            }
        }
        // Agar pehle se INACTIVE hai (manually) → flag mat set karo, state preserve karo
    } elseif($today_count < $limit && $cr['deactivated_by_coupon'] == 1){
        // Coupon ne deactivate kiya tha, count ab limit se kam hai (naya din) → wapas ACTIVE
        mysqli_query($link, "UPDATE map SET status='ACTIVE' WHERE TRIM(macid)='$mac'");
        mysqli_query($link, "UPDATE mac_coupons SET deactivated_by_coupon=0 WHERE macid='$mac'");
    }
}
// ─────────────────────────────────────────────────────────────────────────────

// Add / Update coupon limit
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_coupon'])){
    $sel_macid = mysqli_real_escape_string($link, trim($_POST['sel_macid']));
    $daily_lim = (int)$_POST['daily_limit'];

    if($sel_macid === ''){
        $error = 'MAC ID select karo!';
    } elseif($daily_lim < 1){
        $error = 'Daily limit kam se kam 1 hona chahiye!';
    } else {
        // Upsert
        $chk = mysqli_query($link, "SELECT id FROM mac_coupons WHERE macid='$sel_macid'");
        if(mysqli_num_rows($chk) > 0){
            mysqli_query($link, "UPDATE mac_coupons SET daily_limit=$daily_lim WHERE macid='$sel_macid'");
            $success = "Coupon limit update ho gayi MAC ID: <b>$sel_macid</b>";
        } else {
            mysqli_query($link, "INSERT INTO mac_coupons (macid, daily_limit) VALUES ('$sel_macid', $daily_lim)");
            $success = "Coupon limit set ho gayi MAC ID: <b>$sel_macid</b>";
        }
    }
}

// Delete coupon config
if(isset($_GET['remove'])){
    $rmac = mysqli_real_escape_string($link, $_GET['remove']);
    // Sirf tabhi ACTIVE karo agar coupon ne deactivate kiya tha (deactivated_by_coupon=1)
    // Manually inactive machines ko touch mat karo
    $flag_res = mysqli_fetch_assoc(mysqli_query($link, "SELECT deactivated_by_coupon FROM mac_coupons WHERE macid='$rmac'"));
    if($flag_res && $flag_res['deactivated_by_coupon'] == 1){
        mysqli_query($link, "UPDATE map SET status='ACTIVE' WHERE TRIM(macid)='$rmac'");
    }
    mysqli_query($link, "DELETE FROM mac_coupons WHERE macid='$rmac'");
    header("location:mac_coupon.php");
    exit();
}

// Edit prefill
$edit_mac   = '';
$edit_limit = '';
if(isset($_GET['edit'])){
    $emac = mysqli_real_escape_string($link, $_GET['edit']);
    $er   = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM mac_coupons WHERE macid='$emac'"));
    if($er){ $edit_mac = $er['macid']; $edit_limit = $er['daily_limit']; }
}

// All MAC IDs from map (for dropdown)
$all_macs = mysqli_query($link, "SELECT macid, name FROM map ORDER BY name ASC");

// Summary stats
$total_macs    = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as c FROM map"))['c'];
$active_macs   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as c FROM map WHERE status='ACTIVE'"))['c'];
$inactive_macs = $total_macs - $active_macs;
$coupon_set    = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as c FROM mac_coupons"))['c'];

// Main table status filter
$filter_status    = isset($_GET['filter_status'])    ? trim($_GET['filter_status'])    : '';
$filter_date_from = (isset($_GET['filter_date_from']) && $_GET['filter_date_from']) ? $_GET['filter_date_from'] : date('Y-m-d');
$filter_date_to   = (isset($_GET['filter_date_to'])   && $_GET['filter_date_to'])   ? $_GET['filter_date_to']   : date('Y-m-d');

$main_where = 'WHERE 1=1';
if($filter_status === 'active'){
    $main_where .= " AND m.status='ACTIVE'";
} elseif($filter_status === 'coupon_khatam'){
    $main_where .= " AND mc.deactivated_by_coupon=1";
} elseif($filter_status === 'inactive'){
    $main_where .= " AND m.status='INACTIVE' AND (mc.deactivated_by_coupon IS NULL OR mc.deactivated_by_coupon=0)";
} elseif($filter_status === 'no_coupon'){
    $main_where .= " AND mc.macid IS NULL";
}

// Pagination
$per_page    = 10;
$cur_page    = max(1, (int)($_GET['pg'] ?? 1));
$offset      = ($cur_page - 1) * $per_page;

// Total count for pagination
$total_count_res = mysqli_fetch_assoc(mysqli_query($link, "
    SELECT COUNT(*) as c FROM map m
    LEFT JOIN mac_coupons mc ON LOWER(TRIM(m.macid)) = LOWER(mc.macid)
    $main_where
"));
$total_filtered = (int)$total_count_res['c'];
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$cur_page       = min($cur_page, $total_pages);
$offset         = ($cur_page - 1) * $per_page;

// Main data: map LEFT JOIN mac_coupons + today's count — paginated
$main_data = mysqli_query($link, "
    SELECT 
        m.id, m.name, m.macid, m.status,
        mc.daily_limit, mc.deactivated_by_coupon,
        (SELECT COALESCE(SUM(s.user_count),0) FROM system_name_data s WHERE LOWER(TRIM(s.macId)) = LOWER(TRIM(m.macid)) AND s.system_date = CURDATE()) as today_count
    FROM map m
    LEFT JOIN mac_coupons mc ON LOWER(TRIM(m.macid)) = LOWER(mc.macid)
    $main_where
    ORDER BY mc.daily_limit DESC, m.name ASC
    LIMIT $per_page OFFSET $offset
");

?>

<div class="content" style="min-height:610px;">
<div class="animated fadeIn">
<div class="container-fluid">

    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible mt-2" style="border-radius:10px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible mt-2" style="border-radius:10px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row mb-3">
        <div class="col-6 col-md-3">
            <div class="card text-white text-center py-3" style="background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;">
                <h3 class="mb-0"><?= $total_macs ?></h3>
                <small>Total MAC IDs</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white text-center py-3" style="background:linear-gradient(135deg,#11998e,#38ef7d);border-radius:12px;">
                <h3 class="mb-0"><?= $active_macs ?></h3>
                <small>Active</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white text-center py-3" style="background:linear-gradient(135deg,#e94560,#c0392b);border-radius:12px;">
                <h3 class="mb-0"><?= $inactive_macs ?></h3>
                <small>Inactive</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white text-center py-3" style="background:linear-gradient(135deg,#f7971e,#ffd200);border-radius:12px;">
                <h3 class="mb-0"><?= $coupon_set ?></h3>
                <small>Coupon Configured</small>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- Set Coupon Form -->
        <div class="col-lg-4">
            <div class="card" style="border-radius:12px;">
                <div class="card-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px 12px 0 0;">
                    <strong><i class="fa fa-ticket"></i> <?= $edit_mac ? 'Edit Coupon Limit' : 'Set Coupon Limit' ?></strong>
                    <?php if($edit_mac): ?>
                        <a href="mac_coupon.php" class="btn btn-sm btn-light float-right">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label><b>MAC ID Select Karo</b></label>
                            <?php if($edit_mac): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($edit_mac) ?>" readonly>
                                <input type="hidden" name="sel_macid" value="<?= htmlspecialchars($edit_mac) ?>">
                            <?php else: ?>
                                <select name="sel_macid" id="sel_macid_select" class="form-control" required>
                                    <option value="">-- MAC ID chunein --</option>
                                    <?php
                                    mysqli_data_seek($all_macs, 0);
                                    while($m = mysqli_fetch_assoc($all_macs)):
                                    ?>
                                        <option value="<?= htmlspecialchars($m['macid']) ?>">
                                            <?= htmlspecialchars($m['name']) ?> — <?= htmlspecialchars($m['macid']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label><b>Daily Coupon Limit</b></label>
                            <input type="number" name="daily_limit" class="form-control"
                                   placeholder="Har roz kitne allowed (e.g. 50)"
                                   min="1" required
                                   value="<?= $edit_limit ?>">
                            <small class="text-muted">Jab aaj ka count is limit ko pahunchega, MAC ID INACTIVE ho jayegi.</small>
                        </div>
                        <button type="submit" name="save_coupon"
                                class="btn btn-block text-white"
                                style="background:linear-gradient(135deg,#667eea,#764ba2);border-radius:8px;font-weight:600;">
                            <i class="fa fa-save"></i> <?= $edit_mac ? 'Update Limit' : 'Set Limit' ?>
                        </button>
                    </form>

                    <div class="alert alert-info mt-3 mb-0" style="border-radius:8px;font-size:13px;">
                        <i class="fa fa-info-circle"></i>
                        <b>Auto-reset:</b> Naye din mein aaj ka count 0 ho jata hai, isliye MAC ID wapas <b>ACTIVE</b> ho jaati hai automatically.
                    </div>
                </div>
            </div>
        </div>

        <!-- MAC ID Table -->
        <div class="col-lg-8">
            <div class="card" style="border-radius:12px;">
                <div class="card-header" style="border-radius:12px 12px 0 0;">
                    <strong><i class="fa fa-list"></i> MAC ID Coupon Status</strong>
                    <span class="badge badge-primary float-right mt-1 mr-2"><?= mysqli_num_rows($main_data) ?> MAC IDs</span>
                </div>
                <!-- Filter Form -->
                <div class="card-body border-bottom py-2 px-3" style="background:#f8f9fa;border-radius:0;">
                    <form method="GET" action="mac_coupon.php" class="form-inline flex-wrap" style="gap:8px;">
                        <input type="hidden" name="tab" value="main">
                        <div class="form-group mr-2 mb-1">
                            <label class="mr-1" style="font-size:13px;font-weight:600;">Status:</label>
                            <select name="filter_status" class="form-control form-control-sm">
                                <option value="" <?= $filter_status==='' ? 'selected':'' ?>>Sab</option>
                                <option value="active"        <?= $filter_status==='active'        ? 'selected':'' ?>>Active</option>
                                <option value="coupon_khatam" <?= $filter_status==='coupon_khatam' ? 'selected':'' ?>>Coupon Khatam</option>
                                <option value="inactive"      <?= $filter_status==='inactive'      ? 'selected':'' ?>>Inactive (Manual)</option>
                                <option value="no_coupon"     <?= $filter_status==='no_coupon'     ? 'selected':'' ?>>No Coupon</option>
                            </select>
                        </div>
                        <div class="form-group mr-2 mb-1">
                            <label class="mr-1" style="font-size:13px;font-weight:600;">From:</label>
                            <input type="date" name="filter_date_from" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($filter_date_from) ?>">
                        </div>
                        <div class="form-group mr-2 mb-1">
                            <label class="mr-1" style="font-size:13px;font-weight:600;">To:</label>
                            <input type="date" name="filter_date_to" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($filter_date_to) ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary mb-1">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <a href="mac_coupon.php" class="btn btn-sm btn-secondary mb-1">
                            <i class="fa fa-times"></i> Reset
                        </a>
                        <a href="mac_coupon.php?excel=1&xstatus=<?= urlencode($filter_status) ?>&xdate_from=<?= urlencode($filter_date_from) ?>&xdate_to=<?= urlencode($filter_date_to) ?>"
                           class="btn btn-sm btn-success mb-1 ml-auto">
                            <i class="fa fa-file-excel-o"></i> Download Excel
                        </a>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Name / MAC ID</th>
                                <th class="text-center">Daily Limit</th>
                                <th class="text-center">Aaj Hua</th>
                                <th class="text-center">Bacha</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 1;
                        while($row = mysqli_fetch_assoc($main_data)):
                            $has_coupon   = !is_null($row['daily_limit']);
                            $limit        = (int)($row['daily_limit'] ?? 0);
                            $used         = (int)$row['today_count'];
                            $remaining    = $has_coupon ? max(0, $limit - $used) : '—';
                            $pct          = ($has_coupon && $limit > 0) ? min(100, round($used/$limit*100)) : 0;

                            if($row['status'] === 'ACTIVE'){
                                $badge = '<span class="badge badge-success"><i class="fa fa-check"></i> Active</span>';
                            } elseif($row['deactivated_by_coupon']){
                                $badge = '<span class="badge badge-warning"><i class="fa fa-ticket"></i> Coupon Khatam</span>';
                            } else {
                                $badge = '<span class="badge badge-secondary"><i class="fa fa-ban"></i> Inactive</span>';
                            }

                            // Row highlight if coupon exhausted
                            $rowClass = ($row['deactivated_by_coupon']) ? 'table-warning' : '';
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td><?= $i++ ?></td>
                                <td>
                                    <b><?= htmlspecialchars($row['name']) ?></b><br>
                                    <small class="text-muted" style="font-size:11px;"><?= htmlspecialchars($row['macid']) ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if($has_coupon): ?>
                                        <b><?= $limit ?></b>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <b><?= $used ?></b>
                                    <?php if($has_coupon && $limit > 0): ?>
                                    <div class="progress mt-1" style="height:5px;border-radius:4px;">
                                        <div class="progress-bar <?= $pct>=100?'bg-danger':($pct>=70?'bg-warning':'bg-success') ?>"
                                             style="width:<?= $pct ?>%"></div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($has_coupon): ?>
                                        <b class="<?= $remaining==0?'text-danger':($remaining<=$limit*0.2?'text-warning':'text-success') ?>">
                                            <?= $remaining ?>
                                        </b>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $badge ?></td>
                                <td class="text-center">
                                    <a href="mac_coupon.php?edit=<?= urlencode($row['macid']) ?>"
                                       class="btn btn-xs btn-warning" title="Edit Limit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php if($has_coupon): ?>
                                    <a href="mac_coupon.php?remove=<?= urlencode($row['macid']) ?>"
                                       class="btn btn-xs btn-danger ml-1" title="Remove Coupon"
                                       onclick="return confirm('Is MAC ID ka coupon remove karein?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" style="background:#f8f9fa;">
                        <small class="text-muted">
                            Showing <b><?= $offset+1 ?>–<?= min($offset+$per_page, $total_filtered) ?></b> of <b><?= $total_filtered ?></b> MAC IDs
                        </small>
                        <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Prev -->
                            <li class="page-item <?= $cur_page<=1 ? 'disabled':'' ?>">
                                <a class="page-link" href="?pg=<?= $cur_page-1 ?>&filter_status=<?= urlencode($filter_status) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>">
                                    <i class="fa fa-chevron-left"></i>
                                </a>
                            </li>
                            <!-- Page numbers -->
                            <?php
                            $start_p = max(1, $cur_page - 2);
                            $end_p   = min($total_pages, $cur_page + 2);
                            if($start_p > 1): ?>
                                <li class="page-item"><a class="page-link" href="?pg=1&filter_status=<?= urlencode($filter_status) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>">1</a></li>
                                <?php if($start_p > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                            <?php endif; ?>
                            <?php for($p = $start_p; $p <= $end_p; $p++): ?>
                                <li class="page-item <?= $p==$cur_page ? 'active':'' ?>">
                                    <a class="page-link" href="?pg=<?= $p ?>&filter_status=<?= urlencode($filter_status) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if($end_p < $total_pages): ?>
                                <?php if($end_p < $total_pages-1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link" href="?pg=<?= $total_pages ?>&filter_status=<?= urlencode($filter_status) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>"><?= $total_pages ?></a></li>
                            <?php endif; ?>
                            <!-- Next -->
                            <li class="page-item <?= $cur_page>=$total_pages ? 'disabled':'' ?>">
                                <a class="page-link" href="?pg=<?= $cur_page+1 ?>&filter_status=<?= urlencode($filter_status) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>">
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>


</div>
</div>
</div>


<!-- Select2 CSS (can load before jQuery) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    color: #495057;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
.select2-container { width: 100% !important; }
</style>

<?php include('layout/footer.php'); ?>

<!-- Select2 JS — must come after footer.php which loads jQuery -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
jQuery(document).ready(function($){
    $('#sel_macid_select').select2({
        placeholder: '-- MAC ID chunein (search karein) --',
        allowClear: true,
        width: '100%'
    });
});
</script>
