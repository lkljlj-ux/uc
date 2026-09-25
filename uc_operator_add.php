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

header('Cache-Control: no-store, no-cache, must-revalidate, private');

function ucSavedValueControl($field, $operatorId, $label, $rows = 3){
    $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $field = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');
    return '<div class="uc-saved-value mt-2" data-field="' . $field . '" data-operator-id="' . (int)$operatorId . '">'
        . '<button type="button" class="btn btn-outline-secondary btn-sm uc-reveal">Purani value dekhein</button>'
        . '<textarea class="form-control mt-2 uc-saved-text" rows="' . (int)$rows . '" readonly hidden aria-label="Saved ' . $label . '"></textarea>'
        . '<small class="text-danger uc-reveal-error" hidden></small>'
        . '</div>';
}

$success = '';
$error = '';

if(empty($_SESSION['uc_operator_csrf'])){
    $_SESSION['uc_operator_csrf'] = bin2hex(random_bytes(32));
}

$tableSql = "CREATE TABLE IF NOT EXISTS uc_operators (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    operator_name VARCHAR(150) NOT NULL,
    aadhaar_encrypted TEXT NOT NULL,
    aadhaar_last4 CHAR(4) NOT NULL,
    auth_token_encrypted TEXT NOT NULL,
    bio_token_encrypted TEXT NOT NULL,
    pid_data_encrypted LONGTEXT NOT NULL,
    otp_encrypted TEXT NOT NULL,
    verify_otp_encrypted TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_uc_operator_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if(!mysqli_query($link, $tableSql)){
    $error = 'UC Operator storage ready nahi ho saka: ' . mysqli_error($link);
}

$editRow = null;
$editId = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update'
    ? filter_var($_POST['operator_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : (isset($_GET['edit']) ? filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null);
if($editId !== null && $error === ''){
    if($editId === false){
        $error = 'Valid operator chunein.';
    } else {
        $stmt = mysqli_prepare($link, "SELECT id, operator_name, aadhaar_last4 FROM uc_operators WHERE id = ?");
        if($stmt){
            mysqli_stmt_bind_param($stmt, 'i', $editId);
            mysqli_stmt_execute($stmt);
            $editRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }
        if(!$editRow){
            $error = 'Operator nahi mila.';
            if(($_POST['action'] ?? '') === 'update'){
                $_POST = [];
            }
        }
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $error === ''){
    $csrf = $_POST['csrf_token'] ?? '';
    if(!hash_equals($_SESSION['uc_operator_csrf'], $csrf)){
        $error = 'Form session expire ho gaya. Page refresh karke dobara submit karein.';
    } elseif(in_array($_POST['action'] ?? '', ['set_verify_otp', 'set_otp_uc'], true)){
        $isUcToken = $_POST['action'] === 'set_otp_uc';
        $field = $isUcToken ? 'otp' : 'verify_otp';
        $column = $isUcToken ? 'otp_encrypted' : 'verify_otp_encrypted';
        $label = $isUcToken ? 'verify-otp-uc' : 'verify-otp';
        $operatorId = filter_var($_POST['operator_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $token = trim($_POST[$field] ?? '');
        if($operatorId === false || $token === '' || strlen($token) > 10000){
            $error = "Valid operator aur 10000 characters tak ka $label token dein.";
        } else {
            try {
                $encrypted = ucEncrypt($token);
                $stmt = mysqli_prepare($link, "UPDATE uc_operators SET $column = ? WHERE id = ?");
                if(!$stmt){
                    throw new RuntimeException(mysqli_error($link));
                }
                mysqli_stmt_bind_param($stmt, 'si', $encrypted, $operatorId);
                if(!mysqli_stmt_execute($stmt)){
                    throw new RuntimeException(mysqli_stmt_error($stmt));
                }
                if(mysqli_stmt_affected_rows($stmt) === 0){
                    $error = 'Operator nahi mila.';
                } else {
                    $success = "$label token update ho gaya.";
                    $_SESSION['uc_operator_csrf'] = bin2hex(random_bytes(32));
                }
                mysqli_stmt_close($stmt);
            } catch(Throwable $e){
                $error = "$label token save nahi ho saka.";
            }
        }
    } elseif(($_POST['action'] ?? '') === 'update'){
        $operatorName = trim($_POST['operator_name'] ?? '');
        $aadhaarNo = trim($_POST['aadhaar_no'] ?? '');
        $authToken = trim($_POST['auth_token'] ?? '');
        $bioToken = trim($_POST['bio_token'] ?? '');
        $pidData = trim($_POST['pid_data'] ?? '');
        $otp = trim($_POST['otp'] ?? '');
        $verifyOtp = trim($_POST['verify_otp'] ?? '');

        if($operatorName === '' || strlen($operatorName) > 150){
            $error = 'Operator Name 1 se 150 characters ka hona chahiye.';
        } elseif($aadhaarNo !== '' && !preg_match('/^\d{12}$/', $aadhaarNo)){
            $error = 'Naya Aadhaar No. exactly 12 digits ka hona chahiye.';
        } elseif(strlen($authToken) > 10000 || strlen($bioToken) > 10000 || strlen($otp) > 10000 || strlen($verifyOtp) > 10000 || strlen($pidData) > 1000000){
            $error = 'Token ya PID Data allowed size se bada hai.';
        } else {
            try {
                $aadhaarEncrypted = $aadhaarNo === '' ? null : ucEncrypt($aadhaarNo);
                $aadhaarLast4 = $aadhaarNo === '' ? null : substr($aadhaarNo, -4);
                $authEncrypted = $authToken === '' ? null : ucEncrypt($authToken);
                $bioEncrypted = $bioToken === '' ? null : ucEncrypt($bioToken);
                $pidEncrypted = $pidData === '' ? null : ucEncrypt($pidData);
                $otpEncrypted = $otp === '' ? null : ucEncrypt($otp);
                $verifyOtpEncrypted = $verifyOtp === '' ? null : ucEncrypt($verifyOtp);

                $stmt = mysqli_prepare($link, "UPDATE uc_operators SET operator_name = ?,
                    aadhaar_encrypted = COALESCE(?, aadhaar_encrypted),
                    aadhaar_last4 = COALESCE(?, aadhaar_last4),
                    auth_token_encrypted = COALESCE(?, auth_token_encrypted),
                    bio_token_encrypted = COALESCE(?, bio_token_encrypted),
                    pid_data_encrypted = COALESCE(?, pid_data_encrypted),
                    otp_encrypted = COALESCE(?, otp_encrypted),
                    verify_otp_encrypted = COALESCE(?, verify_otp_encrypted)
                    WHERE id = ?");
                if(!$stmt){
                    throw new RuntimeException('Update query unavailable');
                }
                mysqli_stmt_bind_param($stmt, 'ssssssssi', $operatorName, $aadhaarEncrypted, $aadhaarLast4,
                    $authEncrypted, $bioEncrypted, $pidEncrypted, $otpEncrypted, $verifyOtpEncrypted, $editId);
                if(!mysqli_stmt_execute($stmt)){
                    throw new RuntimeException(mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);
                $success = 'UC Operator update ho gaya.';
                $_SESSION['uc_operator_csrf'] = bin2hex(random_bytes(32));
                $_POST = [];
                $editRow['operator_name'] = $operatorName;
                if($aadhaarLast4 !== null){
                    $editRow['aadhaar_last4'] = $aadhaarLast4;
                }
            } catch(Throwable $e){
                $error = 'UC Operator update nahi ho saka.';
            }
        }
    } elseif(($_POST['action'] ?? '') === ''){
    $operatorName = trim($_POST['operator_name'] ?? '');
    $aadhaarNo = preg_replace('/\D+/', '', $_POST['aadhaar_no'] ?? '');
    $authToken = trim($_POST['auth_token'] ?? '');
    $bioToken = trim($_POST['bio_token'] ?? '');
    $pidData = trim($_POST['pid_data'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $verifyOtp = trim($_POST['verify_otp'] ?? '');

    if($operatorName === '' || $aadhaarNo === '' || $authToken === '' || $bioToken === '' || $pidData === '' || $otp === '' || $verifyOtp === ''){
        $error = 'Sabhi fields bharna zaroori hai.';
    } elseif(!preg_match('/^\d{12}$/', $aadhaarNo)){
        $error = 'Aadhaar No. exactly 12 digits ka hona chahiye.';
    } elseif(strlen($operatorName) > 150){
        $error = 'Operator Name 150 characters se zyada nahi ho sakta.';
    } elseif(strlen($authToken) > 10000 || strlen($bioToken) > 10000 || strlen($otp) > 10000 || strlen($verifyOtp) > 10000 || strlen($pidData) > 1000000){
        $error = 'Token ya PID Data allowed size se bada hai.';
    } else {
        try {
            $aadhaarEncrypted = ucEncrypt($aadhaarNo);
            $authEncrypted = ucEncrypt($authToken);
            $bioEncrypted = ucEncrypt($bioToken);
            $pidEncrypted = ucEncrypt($pidData);
            $otpEncrypted = ucEncrypt($otp);
            $verifyOtpEncrypted = ucEncrypt($verifyOtp);
            $aadhaarLast4 = substr($aadhaarNo, -4);

            $stmt = mysqli_prepare($link, "INSERT INTO uc_operators
                (operator_name, aadhaar_encrypted, aadhaar_last4, auth_token_encrypted, bio_token_encrypted, pid_data_encrypted, otp_encrypted, verify_otp_encrypted)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if(!$stmt){
                throw new RuntimeException(mysqli_error($link));
            }

            mysqli_stmt_bind_param(
                $stmt,
                'ssssssss',
                $operatorName,
                $aadhaarEncrypted,
                $aadhaarLast4,
                $authEncrypted,
                $bioEncrypted,
                $pidEncrypted,
                $otpEncrypted,
                $verifyOtpEncrypted
            );

            if(!mysqli_stmt_execute($stmt)){
                throw new RuntimeException(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);
            $success = 'UC Operator successfully add ho gaya.';
            $_SESSION['uc_operator_csrf'] = bin2hex(random_bytes(32));
            $_POST = [];
        } catch(Throwable $e){
            $error = 'UC Operator save nahi ho saka: ' . $e->getMessage();
        }
    }
    }
}

$operators = false;
if($error === '' || mysqli_query($link, "SHOW TABLES LIKE 'uc_operators'")){
    $operators = mysqli_query($link, "SELECT id, operator_name, aadhaar_last4, verify_otp_encrypted IS NOT NULL AS has_verify_otp, created_at FROM uc_operators ORDER BY id DESC LIMIT 100");
}
?>

<style>.uc-saved-value [hidden] { display: none !important; }</style>
<div class="content" style="min-height:610px;">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong><i class="fa fa-user-plus"></i> <?= $editRow ? 'UC Operator Edit' : 'UC Operator Add' ?></strong>
                    </div>
                    <div class="card-body">
                        <?php if($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="uc_operator_add.php" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['uc_operator_csrf']) ?>">
                            <?php if($editRow): ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="operator_id" value="<?= (int)$editRow['id'] ?>">
                                 <p class="text-muted">Purani value dekhne ke liye har field ka button dabayein. Replacement field khali chhodne par saved value unchanged rahegi.</p>
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="operator_name"><b>Operator Name</b></label>
                                    <input id="operator_name" type="text" name="operator_name" class="form-control"
                                           maxlength="150" required
                                           value="<?= htmlspecialchars($_POST['operator_name'] ?? $editRow['operator_name'] ?? '') ?>"
                                           placeholder="Operator ka naam">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="aadhaar_no"><b>Aadhaar No.</b></label>
                                    <input id="aadhaar_no" type="text" name="aadhaar_no" class="form-control"
                                           inputmode="numeric" pattern="[0-9]{12}" maxlength="12" <?= $editRow ? '' : 'required' ?>
                                           value="<?= $editRow ? '' : htmlspecialchars($_POST['aadhaar_no'] ?? '') ?>"
                                           placeholder="<?= $editRow ? 'Unchanged (****' . htmlspecialchars($editRow['aadhaar_last4']) . ')' : '12 digit Aadhaar number' ?>">
                                     <?php if($editRow): echo ucSavedValueControl('aadhaar_no', $editRow['id'], 'Aadhaar No.', 1); endif; ?>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="auth_token"><b>Auth Token</b></label>
                                    <textarea id="auth_token" name="auth_token" class="form-control" rows="3"
                                               maxlength="10000" <?= $editRow ? '' : 'required' ?> placeholder="<?= $editRow ? 'Unchanged — replace karne ke liye paste karein' : 'Auth token paste karein' ?>"><?= $editRow ? '' : htmlspecialchars($_POST['auth_token'] ?? '') ?></textarea>
                                     <?php if($editRow): echo ucSavedValueControl('auth_token', $editRow['id'], 'Auth Token'); endif; ?>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="bio_token"><b>Bio Token</b></label>
                                    <textarea id="bio_token" name="bio_token" class="form-control" rows="3"
                                               maxlength="10000" <?= $editRow ? '' : 'required' ?> placeholder="<?= $editRow ? 'Unchanged — replace karne ke liye paste karein' : 'Bio token paste karein' ?>"><?= $editRow ? '' : htmlspecialchars($_POST['bio_token'] ?? '') ?></textarea>
                                     <?php if($editRow): echo ucSavedValueControl('bio_token', $editRow['id'], 'Bio Token'); endif; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="pid_data"><b>PID Data</b></label>
                                <textarea id="pid_data" name="pid_data" class="form-control" rows="5"
                                           maxlength="1000000" <?= $editRow ? '' : 'required' ?> placeholder="<?= $editRow ? 'Unchanged — replace karne ke liye paste karein' : 'PID data paste karein' ?>"><?= $editRow ? '' : htmlspecialchars($_POST['pid_data'] ?? '') ?></textarea>
                             <?php if($editRow): echo ucSavedValueControl('pid_data', $editRow['id'], 'PID Data', 5); endif; ?>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="otp"><b>verify-otp-uc Token</b></label>
                                    <textarea id="otp" name="otp" class="form-control" rows="3"
                                               maxlength="10000" <?= $editRow ? '' : 'required' ?> placeholder="<?= $editRow ? 'Unchanged — replace karne ke liye paste karein' : 'Auth token ki tarah text paste karein' ?>"><?= $editRow ? '' : htmlspecialchars($_POST['otp'] ?? '') ?></textarea>
                                     <?php if($editRow): echo ucSavedValueControl('otp', $editRow['id'], 'verify-otp-uc Token'); endif; ?>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="verify_otp"><b>verify-otp Token</b></label>
                                    <textarea id="verify_otp" name="verify_otp" class="form-control" rows="3"
                                               maxlength="10000" <?= $editRow ? '' : 'required' ?> placeholder="<?= $editRow ? 'Unchanged — replace karne ke liye paste karein' : 'Auth token ki tarah text paste karein' ?>"><?= $editRow ? '' : htmlspecialchars($_POST['verify_otp'] ?? '') ?></textarea>
                                     <?php if($editRow): echo ucSavedValueControl('verify_otp', $editRow['id'], 'verify-otp Token'); endif; ?>
                                </div>
                            </div>

                            <div class="alert alert-info py-2">
                                Aadhaar, tokens aur PID Data encrypted form mein save honge. <?= $editRow ? 'Khali fields ki saved values unchanged rahengi.' : '' ?>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> <?= $editRow ? 'Changes Save Karo' : 'UC Operator Add Karo' ?>
                            </button>
                            <?php if($editRow): ?><a href="uc_operator_add.php" class="btn btn-secondary ml-2">Cancel</a><?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><strong>UC Operators List</strong></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Operator Name</th>
                                        <th>Aadhaar No.</th>
                                        <th>Sensitive Data</th>
                                        <th>verify-otp Token</th>
                                        <th>verify-otp-uc Token</th>
                                        <th>Added On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if($operators && mysqli_num_rows($operators) > 0): ?>
                                    <?php $sr = 1; while($row = mysqli_fetch_assoc($operators)): ?>
                                        <tr>
                                            <td><?= $sr++ ?></td>
                                            <td><?= htmlspecialchars($row['operator_name']) ?></td>
                                            <td><?= htmlspecialchars('********' . $row['aadhaar_last4']) ?></td>
                                            <td><span class="badge badge-success">Encrypted</span></td>
                                            <td>
                                                <form method="POST" action="uc_operator_add.php" autocomplete="off" class="form-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['uc_operator_csrf']) ?>">
                                                    <input type="hidden" name="action" value="set_verify_otp">
                                                    <input type="hidden" name="operator_id" value="<?= (int)$row['id'] ?>">
                                                    <textarea name="verify_otp" class="form-control form-control-sm mr-2 mb-1"
                                                              aria-label="verify-otp Token for <?= htmlspecialchars($row['operator_name']) ?>"
                                                              rows="2" maxlength="10000" required
                                                              placeholder="<?= $row['has_verify_otp'] ? 'Replace token' : 'Paste token' ?>"></textarea>
                                                    <button type="submit" class="btn btn-sm btn-primary mb-1"><?= $row['has_verify_otp'] ? 'Update' : 'Save' ?></button>
                                                </form>
                                                 <?= ucSavedValueControl('verify_otp', $row['id'], 'verify-otp Token', 2) ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="uc_operator_add.php" autocomplete="off" class="form-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['uc_operator_csrf']) ?>">
                                                    <input type="hidden" name="action" value="set_otp_uc">
                                                    <input type="hidden" name="operator_id" value="<?= (int)$row['id'] ?>">
                                                    <textarea name="otp" class="form-control form-control-sm mr-2 mb-1"
                                                              aria-label="verify-otp-uc Token for <?= htmlspecialchars($row['operator_name']) ?>"
                                                              rows="2" maxlength="10000" required placeholder="Replace token"></textarea>
                                                    <button type="submit" class="btn btn-sm btn-primary mb-1">Update</button>
                                                </form>
                                                 <?= ucSavedValueControl('otp', $row['id'], 'verify-otp-uc Token', 2) ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                            <td><a class="btn btn-warning btn-sm" href="uc_operator_add.php?edit=<?= (int)$row['id'] ?>"><i class="fa fa-edit"></i> Edit</a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center text-muted">Abhi koi UC Operator add nahi hua.</td></tr>
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
<script>
document.addEventListener('click', async function (event) {
    const button = event.target.closest('.uc-reveal');
    if (!button) return;
    const container = button.closest('.uc-saved-value');
    const saved = container.querySelector('.uc-saved-text');
    const error = container.querySelector('.uc-reveal-error');
    if (!saved.hidden) {
        saved.value = '';
        saved.hidden = true;
        button.textContent = 'Purani value dekhein';
        return;
    }
    error.hidden = true;
    button.disabled = true;
    button.textContent = 'Loading...';
    try {
        const body = new URLSearchParams({
            csrf_token: document.querySelector('input[name="csrf_token"]').value,
            operator_id: container.dataset.operatorId,
            field: container.dataset.field
        });
        const response = await fetch('uc_operator_value.php', {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Saved value read nahi ho saki.');
        saved.value = data.value;
        saved.hidden = false;
        button.textContent = 'Purani value chhupayein';
    } catch (e) {
        error.textContent = e.message;
        error.hidden = false;
        button.textContent = 'Purani value dekhein';
    } finally {
        button.disabled = false;
    }
});
</script>