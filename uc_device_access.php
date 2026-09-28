<?php
if(($_SERVER['HTTPS'] ?? '') !== 'on'){
    http_response_code(403);
    exit('HTTPS is required for device token administration.');
}
include('layout/header.php');
require_once __DIR__ . '/includes/uc_security.php';

if(!isset($_SESSION['user_token'])){
    header('Location: /login.php');
    exit();
}
if(($_SESSION['user_type'] ?? '') !== 'admin'){
    http_response_code(403);
    exit('Access denied');
}
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Referrer-Policy: no-referrer');

if(empty($_SESSION['uc_device_csrf'])){
    $_SESSION['uc_device_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$newToken = null;
$newMac = '';
$enteredMac = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!is_string($_POST['csrf_token'] ?? null) ||
        !hash_equals($_SESSION['uc_device_csrf'], $_POST['csrf_token'])){
        $error = 'Form session expire ho gaya. Page refresh karein.';
    } elseif(($_POST['action'] ?? '') === 'issue'){
        $rawMac = $_POST['macId'] ?? '';
        $mac = is_string($rawMac) ? ucNormalizeMac($rawMac) : '';
        $enteredMac = is_string($rawMac) ? $rawMac : '';
        if($mac === '' || strlen($mac) > 100 || preg_match('/[\x00-\x1F\x7F]/', $mac)){
            $error = 'Valid MAC ID dein (max 100 characters).';
        } else {
            try {
                $token = bin2hex(random_bytes(32));
                $hash = hash('sha256', $token);
                $stmt = mysqli_prepare($link, 'INSERT INTO uc_device_tokens (macid, token_hash) VALUES (?, ?)');
                mysqli_stmt_bind_param($stmt, 'ss', $mac, $hash);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $newToken = $token;
                $newMac = $mac;
                $success = 'Device token ban gaya. Abhi copy karein; dobara dikhaya nahi jayega.';
                $_SESSION['uc_device_csrf'] = bin2hex(random_bytes(32));
            } catch(Throwable $e){
                $error = 'Device token create nahi ho saka.';
            }
        }
    } elseif(($_POST['action'] ?? '') === 'revoke'){
        $id = filter_var($_POST['token_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if($id === false){
            $error = 'Valid token chunein.';
        } else {
            try {
                $stmt = mysqli_prepare($link, 'UPDATE uc_device_tokens SET revoked_at = CURRENT_TIMESTAMP WHERE id = ? AND revoked_at IS NULL');
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                $changed = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                $success = $changed ? 'Token revoke ho gaya.' : 'Token pehle se revoked hai ya nahi mila.';
                $_SESSION['uc_device_csrf'] = bin2hex(random_bytes(32));
            } catch(Throwable $e){
                $error = 'Token revoke nahi ho saka.';
            }
        }
    } else {
        $error = 'Invalid action.';
    }
}

$tokens = [];
try {
    $result = mysqli_query($link, 'SELECT id, macid, created_at, revoked_at FROM uc_device_tokens ORDER BY id DESC LIMIT 200');
    while($row = mysqli_fetch_assoc($result)){
        $tokens[] = $row;
    }
} catch(Throwable $e){
    $error = 'Device tokens load nahi ho sake.';
}
$transition = getenv('UC_API_SECURITY_MODE') === 'transition';
?>
<div class="content" style="min-height:610px;">
    <div class="animated fadeIn">
        <div class="card">
            <div class="card-header"><strong><i class="fa fa-lock"></i> UC Device Access</strong></div>
            <div class="card-body">
                <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <div class="alert alert-<?= $transition ? 'warning' : 'success' ?>">
                    API security: <strong><?= $transition ? 'Transition mode' : 'Enforced' ?></strong>.
                    <?= $transition ? 'Purana HTTP API shared header ke saath abhi chal raha hai, lekin encrypted nahi hai. Sab devices migrate karne ke baad ise band karein.' : 'Sensitive API requests ke liye HTTPS aur device token zaroori hain.' ?>
                </div>
                <?php if($newToken !== null): ?>
                    <div class="alert alert-warning">
                        <b><?= htmlspecialchars($newMac, ENT_QUOTES, 'UTF-8') ?> ka naya token (sirf abhi dikhega):</b>
                        <textarea class="form-control mt-2" rows="2" readonly aria-label="New device token"><?= htmlspecialchars($newToken, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small>Is token ko sirf us device ke UC_DEVICE_TOKEN environment mein set karein. Kisi report ya URL mein na daalein.</small>
                    </div>
                <?php endif; ?>
                <form method="POST" action="uc_device_access.php" autocomplete="off" class="form-inline mb-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['uc_device_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="issue">
                    <label for="deviceMac" class="mr-2"><b>Device MAC ID</b></label>
                    <input id="deviceMac" name="macId" class="form-control mr-2 mb-2" maxlength="100" required
                           placeholder="Machine MAC ID" value="<?= htmlspecialchars($enteredMac, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-primary mb-2">Naya Token Banayein</button>
                </form>
                <p class="text-muted">Ek device ke liye rotation ke dauran do active tokens rakh sakte hain. Purana token naya client chalne ke baad revoke karein.</p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark"><tr><th>MAC ID</th><th>Created</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach($tokens as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['macid'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $row['revoked_at'] ? 'Revoked' : 'Active' ?></td>
                                <td>
                                    <?php if(!$row['revoked_at']): ?>
                                        <form method="POST" action="uc_device_access.php" onsubmit="return confirm('Is token ko revoke karein? Device ki API access band ho jayegi.')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['uc_device_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="token_id" value="<?= (int)$row['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(!$tokens): ?><tr><td colspan="4" class="text-center text-muted">Abhi koi device token nahi hai.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('layout/footer.php'); ?>