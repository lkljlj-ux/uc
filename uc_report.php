<?php
include('layout/header.php');

if(!isset($_SESSION['user_token'])){
    header("location:" . $basePath . "login.php");
    exit();
}

if(($_SESSION['user_type'] ?? '') !== 'admin'){
    http_response_code(403);
    exit('Access denied');
}

$macFilter = strtoupper(trim((string)($_GET['macId'] ?? '')));
$error = '';
$rows = [];
$summary = ['mac_count' => 0, 'sid_count' => 0, 'total_uploads' => 0];

if(strlen($macFilter) > 100){
    $error = 'MAC ID 100 characters se zyada nahi ho sakta.';
} else {
    $where = $macFilter === '' ? '' : ' WHERE c.macid = ?';
    $summaryStmt = mysqli_prepare($link, "SELECT COUNT(DISTINCT c.macid) AS mac_count,
        COUNT(*) AS sid_count, COALESCE(SUM(c.upload_count), 0) AS total_uploads
        FROM uc_upload_counts c" . $where);
    if($summaryStmt){
        if($macFilter !== ''){
            mysqli_stmt_bind_param($summaryStmt, 's', $macFilter);
        }
        mysqli_stmt_execute($summaryStmt);
        $summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summaryStmt));
        mysqli_stmt_close($summaryStmt);

        $stmt = mysqli_prepare($link, "SELECT c.macid, c.sid, c.upload_count, c.created_at, c.updated_at,
            u.operator_name
            FROM uc_upload_counts c
            LEFT JOIN uc_machine_map m ON m.macid = c.macid
            LEFT JOIN uc_operators u ON u.id = m.uc_operator_id" . $where .
            " ORDER BY c.updated_at DESC LIMIT 500");
        if($stmt){
            if($macFilter !== ''){
                mysqli_stmt_bind_param($stmt, 's', $macFilter);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $rows[] = $row;
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'UC Report load nahi ho saka.';
        }
    } else {
        $error = 'UC Report load nahi ho saka.';
    }
}
?>

<div class="content" style="min-height:610px;">
    <div class="animated fadeIn">
        <div class="card">
            <div class="card-header"><strong><i class="fa fa-bar-chart"></i> UC Report</strong></div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="GET" action="uc_report.php" class="form-inline mb-4">
                    <label for="macId" class="mr-2"><b>MAC ID</b></label>
                    <input id="macId" name="macId" type="text" maxlength="100" class="form-control mr-2 mb-2"
                           value="<?= htmlspecialchars($macFilter, ENT_QUOTES, 'UTF-8') ?>" placeholder="MAC ID se filter karein">
                    <button type="submit" class="btn btn-primary mb-2 mr-2">Filter</button>
                    <a href="uc_report.php" class="btn btn-secondary mb-2">Reset</a>
                </form>

                <div class="row mb-3">
                    <div class="col-md-4 mb-2"><div class="card bg-light"><div class="card-body">
                        <div class="text-muted">MAC IDs</div><strong><?= (int)$summary['mac_count'] ?></strong>
                    </div></div></div>
                    <div class="col-md-4 mb-2"><div class="card bg-light"><div class="card-body">
                        <div class="text-muted">SIDs</div><strong><?= (int)$summary['sid_count'] ?></strong>
                    </div></div></div>
                    <div class="col-md-4 mb-2"><div class="card bg-light"><div class="card-body">
                        <div class="text-muted">Total Uploads</div><strong><?= htmlspecialchars((string)$summary['total_uploads'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div></div></div>
                </div>

                <p class="text-muted">Har POST us MAC aur SID ka count 1 badhata hai. Neeche latest 500 entries dikh rahi hain.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr><th>MAC ID</th><th>UC Operator</th><th>SID</th><th>Count</th><th>First Upload</th><th>Last Upload</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['macid'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['operator_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="overflow-wrap:anywhere;max-width:300px;"><?= htmlspecialchars($row['sid'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$row['upload_count'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(!$rows): ?>
                                <tr><td colspan="6" class="text-center text-muted">Abhi koi upload count nahi mila.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('layout/footer.php'); ?>