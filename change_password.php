<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:login.php");
    exit();
}

$success = '';
$error   = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pass'])){
    $current  = md5(trim($_POST['current_password']));
    $new_pass = trim($_POST['new_password']);
    $confirm  = trim($_POST['confirm_password']);

    $username = mysqli_real_escape_string($link, $_SESSION['user_token']);

    // Verify current password
    $res = mysqli_query($link, "SELECT id FROM admins WHERE username='$username' AND password='$current'");
    if(!$res || mysqli_num_rows($res) == 0){
        $error = 'Current password galat hai!';
    } elseif(strlen($new_pass) < 6){
        $error = 'New password kam se kam 6 characters ka hona chahiye!';
    } elseif($new_pass !== $confirm){
        $error = 'New password aur Confirm password match nahi kar rahe!';
    } else {
        $hashed = md5($new_pass);
        mysqli_query($link, "UPDATE admins SET password='$hashed' WHERE username='$username'");
        $success = 'Password successfully change ho gaya!';
    }
}
?>

<div class="content" style="min-height:610px;">
<div class="animated fadeIn">
<div class="container-fluid">
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card" style="border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.1);">
            <div class="card-header" style="background:linear-gradient(135deg,#e94560,#c0392b);border-radius:16px 16px 0 0;padding:20px 24px;">
                <h5 class="mb-0 text-white"><i class="fa fa-key"></i> Change Password</h5>
            </div>
            <div class="card-body" style="padding:30px;">

                <?php if($success): ?>
                    <div class="alert alert-success" style="border-radius:10px;">
                        <i class="fa fa-check-circle"></i> <?= $success ?>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger" style="border-radius:10px;">
                        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#e94560,#c0392b);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
                        <i class="fa fa-lock" style="color:#fff;font-size:26px;"></i>
                    </div>
                    <p class="text-muted mb-0" style="font-size:13px;">Logged in as: <b><?= htmlspecialchars($_SESSION['user_token']) ?></b></p>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label><b>Current Password</b></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            </div>
                            <input type="password" name="current_password" id="curr_pass"
                                   class="form-control" placeholder="Purana password daalo" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleField('curr_pass','eye1')">
                                    <i class="fa fa-eye" id="eye1"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><b>New Password</b></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-key"></i></span>
                            </div>
                            <input type="password" name="new_password" id="new_pass"
                                   class="form-control" placeholder="Naya password daalo (min 6 chars)" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleField('new_pass','eye2')">
                                    <i class="fa fa-eye" id="eye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><b>Confirm New Password</b></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-check"></i></span>
                            </div>
                            <input type="password" name="confirm_password" id="conf_pass"
                                   class="form-control" placeholder="Password dobara daalo" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleField('conf_pass','eye3')">
                                    <i class="fa fa-eye" id="eye3"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="change_pass" class="btn btn-block mt-3"
                            style="background:linear-gradient(135deg,#e94560,#c0392b);color:#fff;border-radius:10px;padding:12px;font-weight:600;font-size:15px;border:none;">
                        <i class="fa fa-save"></i> Password Change Karo
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>

<script>
function toggleField(fieldId, eyeId){
    var f = document.getElementById(fieldId);
    var e = document.getElementById(eyeId);
    if(f.type === 'password'){
        f.type = 'text';
        e.className = 'fa fa-eye-slash';
    } else {
        f.type = 'password';
        e.className = 'fa fa-eye';
    }
}
</script>

<?php include('layout/footer.php'); ?>
