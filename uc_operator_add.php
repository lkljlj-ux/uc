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
    } else {
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

<div class="content" style="min-height:610px;">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong><i class="fa fa-user-plus"></i> UC Operator Add</strong>
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

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="operator_name"><b>Operator Name</b></label>
                                    <input id="operator_name" type="text" name="operator_name" class="form-control"
                                           maxlength="150" required
                                           value="<?= htmlspecialchars($_POST['operator_name'] ?? '') ?>"
                                           placeholder="Operator ka naam">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="aadhaar_no"><b>Aadhaar No.</b></label>
                                    <input id="aadhaar_no" type="text" name="aadhaar_no" class="form-control"
                                           inputmode="numeric" pattern="[0-9]{12}" maxlength="12" required
                                           value="<?= htmlspecialchars($_POST['aadhaar_no'] ?? '') ?>"
                                           placeholder="12 digit Aadhaar number">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="auth_token"><b>Auth Token</b></label>
                                    <textarea id="auth_token" name="auth_token" class="form-control" rows="3"
                                              maxlength="10000" required placeholder="Auth token paste karein"><?= htmlspecialchars($_POST['auth_token'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="bio_token"><b>Bio Token</b></label>
                                    <textarea id="bio_token" name="bio_token" class="form-control" rows="3"
                                              maxlength="10000" required placeholder="Bio token paste karein"><?= htmlspecialchars($_POST['bio_token'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="pid_data"><b>PID Data</b></label>
                                <textarea id="pid_data" name="pid_data" class="form-control" rows="5"
                                          maxlength="1000000" required placeholder="PID data paste karein"><?= htmlspecialchars($_POST['pid_data'] ?? '') ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="otp"><b>verify-otp-uc Token</b></label>
                                    <textarea id="otp" name="otp" class="form-control" rows="3"
                                              maxlength="10000" required placeholder="Auth token ki tarah text paste karein"><?= htmlspecialchars($_POST['otp'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="verify_otp"><b>verify-otp Token</b></label>
                                    <textarea id="verify_otp" name="verify_otp" class="form-control" rows="3"
                                              maxlength="10000" required placeholder="Auth token ki tarah text paste karein"><?= htmlspecialchars($_POST['verify_otp'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="alert alert-info py-2">
                                Aadhaar, tokens aur PID Data encrypted form mein save honge.
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> UC Operator Add Karo
                            </button>
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
                                            </td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted">Abhi koi UC Operator add nahi hua.</td></tr>
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