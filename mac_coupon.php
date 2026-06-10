<?php
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

    // Aaj ka count from test table
    $cnt_res = mysqli_query($link, "SELECT COUNT(*) as c FROM test WHERE macid='$mac' AND DATE(created_at)=CURDATE()");
    $today_count = (int)mysqli_fetch_assoc($cnt_res)['c'];

    if($today_count >= $limit && $limit > 0){
        // Coupon khatam → INACTIVE karo aur flag set karo
        mysqli_query($link, "UPDATE map SET status='INACTIVE' WHERE macid='$mac'");
        mysqli_query($link, "UPDATE mac_coupons SET deactivated_by_coupon=1 WHERE macid='$mac'");
    } elseif($today_count < $limit && $cr['deactivated_by_coupon'] == 1){
        // Naya din / count kam hai → wapas ACTIVE karo
        mysqli_query($link, "UPDATE map SET status='ACTIVE' WHERE macid='$mac'");
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
    mysqli_query($link, "DELETE FROM mac_coupons WHERE macid='$rmac'");
    // Reset map status to active if it was deactivated by coupon
    mysqli_query($link, "UPDATE map SET status='ACTIVE' WHERE macid='$rmac'");
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

// Main data: map LEFT JOIN mac_coupons + today's count
$main_data = mysqli_query($link, "
    SELECT 
        m.id, m.name, m.macid, m.status,
        mc.daily_limit, mc.deactivated_by_coupon,
        (SELECT COUNT(*) FROM test t WHERE t.macid = m.macid AND DATE(t.created_at) = CURDATE()) as today_count
    FROM map m
    LEFT JOIN mac_coupons mc ON m.macid = mc.macid
    ORDER BY mc.daily_limit DESC, m.name ASC
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
                                <select name="sel_macid" class="form-control" required>
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
                    <span class="badge badge-primary float-right mt-1"><?= $total_macs ?> MAC IDs</span>
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
                </div>
            </div>
        </div>

    </div>
</div>
</div>
</div>

<?php include('layout/footer.php'); ?>
