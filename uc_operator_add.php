<?php
include('layout/header.php');

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
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_uc_operator_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if(!mysqli_query($link, $tableSql)){
    $error = 'UC Operator storage ready nahi ho saka: ' . mysqli_error($link);
}

function ucEncrypt($value){
    $secret = getenv('SESSION_SECRET');
    if($secret === false || $secret === ''){
        throw new RuntimeException('SESSION_SECRET configure nahi hai.');
    }

    $key = hash('sha256', $secret, true);
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if($ciphertext === false){
        throw new RuntimeException('Sensitive data encrypt nahi ho saka.');
    }

    return base64_encode($iv . $tag . $ciphertext);
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $error === ''){
    $operatorName = trim($_POST['operator_name'] ?? '');
    $aadhaarNo = preg_replace('/\D+/', '', $_POST['aadhaar_no'] ?? '');
    $authToken = trim($_POST['auth_token'] ?? '');
    $bioToken = trim($_POST['bio_token'] ?? '');
    $pidData = trim($_POST['pid_data'] ?? '');
    $otp = preg_replace('/\D+/', '', $_POST['otp'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if(!hash_equals($_SESSION['uc_operator_csrf'], $csrf)){
        $error = 'Form session expire ho gaya. Page refresh karke dobara submit karein.';
    } elseif($operatorName === '' || $aadhaarNo === '' || $authToken === '' || $bioToken === '' || $pidData === '' || $otp === ''){
        $error = 'Sabhi fields bharna zaroori hai.';
    } elseif(!preg_match('/^\d{12}$/', $aadhaarNo)){
        $error = 'Aadhaar No. exactly 12 digits ka hona chahiye.';
    } elseif(!preg_match('/^\d{4,8}$/', $otp)){
        $error = 'OTP 4 se 8 digits ka hona chahiye.';
    } elseif(strlen($operatorName) > 150){
        $error = 'Operator Name 150 characters se zyada nahi ho sakta.';
    } elseif(strlen($authToken) > 10000 || strlen($bioToken) > 10000 || strlen($pidData) > 1000000){
        $error = 'Token ya PID Data allowed size se bada hai.';
    } else {
        try {
            $aadhaarEncrypted = ucEncrypt($aadhaarNo);
            $authEncrypted = ucEncrypt($authToken);
            $bioEncrypted = ucEncrypt($bioToken);
            $pidEncrypted = ucEncrypt($pidData);
            $otpEncrypted = ucEncrypt($otp);
            $aadhaarLast4 = substr($aadhaarNo, -4);

            $stmt = mysqli_prepare($link, "INSERT INTO uc_operators
                (operator_name, aadhaar_encrypted, aadhaar_last4, auth_token_encrypted, bio_token_encrypted, pid_data_encrypted, otp_encrypted)
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            if(!$stmt){
                throw new RuntimeException(mysqli_error($link));
            }

            mysqli_stmt_bind_param(
                $stmt,
                'sssssss',
                $operatorName,
                $aadhaarEncrypted,
                $aadhaarLast4,
                $authEncrypted,
                $bioEncrypted,
                $pidEncrypted,
                $otpEncrypted
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

$operators = false;
if($error === '' || mysqli_query($link, "SHOW TABLES LIKE 'uc_operators'")){
    $operators = mysqli_query($link, "SELECT id, operator_name, aadhaar_last4, created_at FROM uc_operators ORDER BY id DESC LIMIT 100");
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
                                    <label for="otp"><b>OTP</b></label>
                                    <input id="otp" type="password" name="otp" class="form-control"
                                           inputmode="numeric" pattern="[0-9]{4,8}" minlength="4" maxlength="8"
                                           required placeholder="4 se 8 digit OTP">
                                </div>
                            </div>

                            <div class="alert alert-info py-2">
                                Aadhaar, tokens, PID Data aur OTP encrypted form mein save honge.
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
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted">Abhi koi UC Operator add nahi hua.</td></tr>
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