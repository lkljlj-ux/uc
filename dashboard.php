<?php 
include('layout/header.php');
if(!isset($_SESSION['user_token'])){ 
    header("location:login.php");
    exit();
}

// Live Stats Queries
$total_mac   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM map"))['c'] ?? 0;
$active_mac  = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM map WHERE status='ACTIVE'"))['c'] ?? 0;
$inactive_mac= mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM map WHERE status='INACTIVE'"))['c'] ?? 0;
$today_reg   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COALESCE(SUM(user_count),0) AS c FROM system_name_data WHERE system_date=CURDATE()"))['c'] ?? 0;
?>
        <!-- Content -->
        <div class="content" style="min-height: 610px;">
            <div class="animated fadeIn">
                <?php if($_SESSION['user_type']=='admin'){?>

<style>
.dash-card {
    border-radius: 12px;
    padding: 22px 20px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.12);
    margin-bottom: 20px;
    transition: transform 0.2s;
}
.dash-card:hover { transform: translateY(-3px); }
.dash-card .dc-icon {
    font-size: 38px;
    opacity: 0.85;
    min-width: 48px;
    text-align: center;
}
.dash-card .dc-body { flex: 1; }
.dash-card .dc-number {
    font-size: 36px;
    font-weight: 700;
    line-height: 1;
}
.dash-card .dc-label {
    font-size: 13px;
    opacity: 0.88;
    margin-top: 4px;
    font-weight: 500;
    letter-spacing: 0.4px;
    text-transform: uppercase;
}
.dc-blue    { background: linear-gradient(135deg, #1a73e8, #0d47a1); }
.dc-green   { background: linear-gradient(135deg, #16a34a, #065f46); }
.dc-red     { background: linear-gradient(135deg, #dc2626, #7f1d1d); }
.dc-orange  { background: linear-gradient(135deg, #ea580c, #78350f); }
</style>

<div class="row" style="padding: 18px 10px 0;">
    <!-- Total MACs -->
    <div class="col-lg-3 col-md-6">
        <div class="dash-card dc-blue">
            <div class="dc-icon"><i class="fa fa-microchip"></i></div>
            <div class="dc-body">
                <div class="dc-number"><?= number_format($total_mac) ?></div>
                <div class="dc-label">Total MACs</div>
            </div>
        </div>
    </div>

    <!-- Active MACs -->
    <div class="col-lg-3 col-md-6">
        <div class="dash-card dc-green">
            <div class="dc-icon"><i class="fa fa-check-circle"></i></div>
            <div class="dc-body">
                <div class="dc-number"><?= number_format($active_mac) ?></div>
                <div class="dc-label">Active MACs</div>
            </div>
        </div>
    </div>

    <!-- Inactive MACs -->
    <div class="col-lg-3 col-md-6">
        <div class="dash-card dc-red">
            <div class="dc-icon"><i class="fa fa-times-circle"></i></div>
            <div class="dc-body">
                <div class="dc-number"><?= number_format($inactive_mac) ?></div>
                <div class="dc-label">Inactive MACs</div>
            </div>
        </div>
    </div>

    <!-- Today's Registrations -->
    <div class="col-lg-3 col-md-6">
        <div class="dash-card dc-orange">
            <div class="dc-icon"><i class="fa fa-calendar-check-o"></i></div>
            <div class="dc-body">
                <div class="dc-number"><?= number_format($today_reg) ?></div>
                <div class="dc-label">Aaj ki Registrations</div>
            </div>
        </div>
    </div>
</div>

                <?php }?>
            </div>
        </div>

<?php include('layout/footer.php'); ?>
