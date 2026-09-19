<?php
include('layout/header.php');
require_once __DIR__ . '/includes/uc_security.php';

if(!isset($_SESSION['user_token'])){
    header("location:" . $basePath . "login.php");
    exit();
}
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin'){
    http_response_code(403);
    exit('Access denied');
}

$success = '';
$error = '';
if(empty($_SESSION['uc_map_csrf'])){
    $_SESSION['uc_map_csrf'] = bin2hex(random_bytes(32));
}

$createMap = "CREATE TABLE IF NOT EXISTS uc_machine_map (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    macid VARCHAR(100) NOT NULL,
    uc_operator_id INT UNSIGNED NOT NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_uc_machine_macid (macid),
    KEY idx_uc_machine_operator (uc_operator_id),
    CONSTRAINT fk_uc_machine_operator FOREIGN KEY (uc_operator_id) REFERENCES uc_operators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if(!mysqli_query($link, $createMap)){
    $error = 'UC Map storage ready nahi ho saka: ' . mysqli_error($link);
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $error === ''){
    $csrf = $_POST['csrf_token'] ?? '';
    if(!hash_equals($_SESSION['uc_map_csrf'], $csrf)){
        $error = 'Form session expire ho gaya. Page refresh karein.';
    } elseif(isset($_POST['delete_id'])){
        $deleteId = (int)$_POST['delete_id'];
        $stmt = mysqli_prepare($link, "DELETE FROM uc_machine_map WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $deleteId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = 'UC Machine mapping delete ho gaya.';
    } else {
        $editId = (int)($_POST['edit_id'] ?? 0);
        $macId = ucNormalizeMac($_POST['macid'] ?? '');
        $operatorId = (int)($_POST['uc_operator_id'] ?? 0);
        $status = ($_POST['status'] ?? '') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE';

        if($macId === '' || $operatorId < 1){
            $error = 'MAC ID aur UC Operator select karna zaroori hai.';
        } elseif(strlen($macId) > 100){
            $error = 'MAC ID bahut lamba hai.';
        } else {
            $operatorCheck = mysqli_prepare($link, "SELECT id FROM uc_operators WHERE id = ?");
            mysqli_stmt_bind_param($operatorCheck, 'i', $operatorId);
            mysqli_stmt_execute($operatorCheck);
            $operatorExists = mysqli_stmt_get_result($operatorCheck);
            mysqli_stmt_close($operatorCheck);

            if(mysqli_num_rows($operatorExists) === 0){
                $error = 'Selected UC Operator nahi mila.';
            } else {
                if($editId > 0){
                    $stmt = mysqli_prepare($link, "UPDATE uc_machine_map SET macid = ?, uc_operator_id = ?, status = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, 'sisi', $macId, $operatorId, $status, $editId);
                } else {
                    $stmt = mysqli_prepare($link, "INSERT INTO uc_machine_map (macid, uc_operator_id, status) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'sis', $macId, $operatorId, $status);
                }

                if(mysqli_stmt_execute($stmt)){
                    $success = $editId > 0 ? 'UC Machine mapping update ho gaya.' : 'UC Machine mapping add ho gaya.';
                } elseif(mysqli_stmt_errno($stmt) === 1062){
                    $error = 'Yeh MAC ID pehle se mapped hai.';
                } else {
                    $error = 'Mapping save nahi ho saka: ' . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

$editRow = null;
if(isset($_GET['edit'])){
    $editId = (int)$_GET['edit'];
    $stmt = mysqli_prepare($link, "SELECT id, macid, uc_operator_id, status FROM uc_machine_map WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $editId);
    mysqli_stmt_execute($stmt);
    $editRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$operatorOptions = mysqli_query($link, "SELECT id, operator_name, aadhaar_last4 FROM uc_operators ORDER BY operator_name ASC");
$mappings = mysqli_query($link, "SELECT m.id, m.macid, m.status, m.created_at, m.updated_at,
        u.operator_name, u.aadhaar_last4
    FROM uc_machine_map m
    INNER JOIN uc_operators u ON u.id = m.uc_operator_id
    ORDER BY m.id DESC");
?>

<div class="content" style="min-height:610px;">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong><i class="fa fa-sitemap"></i> <?= $editRow ? 'UC Map Machine Edit' : 'UC Map Machine' ?></strong>
                    </div>
                    <div class="card-body">
                        <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                        <form method="POST" action="uc_map.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['uc_map_csrf']) ?>">
                            <input type="hidden" name="edit_id" value="<?= (int)($editRow['id'] ?? 0) ?>">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label><b>MAC ID</b></label>
                                    <input type="text" name="macid" class="form-control" maxlength="100" required
                                           value="<?= htmlspecialchars($editRow['macid'] ?? '') ?>" placeholder="Machine MAC ID">
                                </div>
                                <div class="form-group col-md-4">
                                    <label><b>UC Operator</b></label>
                                    <select name="uc_operator_id" class="form-control" required>
                                        <option value="">-- UC Operator chunein --</option>
                                        <?php if($operatorOptions): while($operator = mysqli_fetch_assoc($operatorOptions)): ?>
                                            <option value="<?= (int)$operator['id'] ?>"
                                                <?= (int)($editRow['uc_operator_id'] ?? 0) === (int)$operator['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($operator['operator_name'] . ' (Aadhaar ****' . $operator['aadhaar_last4'] . ')') ?>
                                            </option>
                                        <?php endwhile; endif; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label><b>Status</b></label>
                                    <select name="status" class="form-control">
                                        <option value="ACTIVE" <?= ($editRow['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                                        <option value="INACTIVE" <?= ($editRow['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> <?= $editRow ? 'Update Karo' : 'Map Karo' ?></button>
                            <?php if($editRow): ?><a href="uc_map.php" class="btn btn-secondary ml-2">Cancel</a><?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><strong>UC Machine Mapping List</strong></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr><th>#</th><th>MAC ID</th><th>UC Operator</th><th>Aadhaar</th><th>Status</th><th>Updated</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                <?php if($mappings && mysqli_num_rows($mappings)): $sr = 1; while($row = mysqli_fetch_assoc($mappings)): ?>
                                    <tr>
                                        <td><?= $sr++ ?></td>
                                        <td><?= htmlspecialchars($row['macid']) ?></td>
                                        <td><?= htmlspecialchars($row['operator_name']) ?></td>
                                        <td><?= htmlspecialchars('********' . $row['aadhaar_last4']) ?></td>
                                        <td><span class="badge badge-<?= $row['status'] === 'ACTIVE' ? 'success' : 'danger' ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                        <td><?= htmlspecialchars($row['updated_at']) ?></td>
                                        <td>
                                            <a class="btn btn-warning btn-sm" href="uc_map.php?edit=<?= (int)$row['id'] ?>"><i class="fa fa-edit"></i> Edit</a>
                                            <form method="POST" action="uc_map.php" class="d-inline" onsubmit="return confirm('Mapping delete karein?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['uc_map_csrf']) ?>">
                                                <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                                                <button class="btn btn-danger btn-sm" type="submit"><i class="fa fa-trash"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="7" class="text-center text-muted">Abhi koi UC machine mapped nahi hai.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('layout/footer.php'); ?>
