<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:login.php");
    exit();
}

$success = '';
$error   = '';

// ── MAC Assignment (POST) ──────────────────────────────────────────────────
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_macs'])){
    $did = (int)$_POST['assign_dist_id'];
    mysqli_query($link, "DELETE FROM distributor_macs WHERE distributor_id=$did");
    if(!empty($_POST['macs']) && is_array($_POST['macs'])){
        foreach($_POST['macs'] as $mac){
            $mac = mysqli_real_escape_string($link, trim($mac));
            if($mac !== ''){
                mysqli_query($link, "INSERT IGNORE INTO distributor_macs (distributor_id, macid) VALUES ($did, '$mac')");
            }
        }
    }
    $success = 'MACs assign ho gayi!';
    $show_assign = $did;
}

// ── Add / Edit Distributor ─────────────────────────────────────────────────
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['distributor_name'])){
    $edit_id  = (int)($_POST['edit_id'] ?? 0);
    $name     = mysqli_real_escape_string($link, trim($_POST['distributor_name']));
    $mobile   = mysqli_real_escape_string($link, trim($_POST['mobile']));
    $city     = mysqli_real_escape_string($link, trim($_POST['city']));
    $state    = mysqli_real_escape_string($link, trim($_POST['state']));
    $status   = ($_POST['status'] === 'active') ? 'active' : 'inactive';
    $uname    = mysqli_real_escape_string($link, trim($_POST['username'] ?? ''));
    $rawpass  = trim($_POST['password'] ?? '');

    if($name === '' || $mobile === ''){
        $error = 'Distributor Name aur Mobile zaroori hain!';
    } else {
        if($edit_id > 0){
            if($uname !== '' && $rawpass !== ''){
                $pass = mysqli_real_escape_string($link, password_hash($rawpass, PASSWORD_DEFAULT));
                $q = "UPDATE distributors SET distributor_name='$name', mobile='$mobile',
                      city='$city', state='$state', status='$status',
                      username='$uname', password='$pass' WHERE id=$edit_id";
            } elseif($uname !== ''){
                $q = "UPDATE distributors SET distributor_name='$name', mobile='$mobile',
                      city='$city', state='$state', status='$status',
                      username='$uname' WHERE id=$edit_id";
            } else {
                $q = "UPDATE distributors SET distributor_name='$name', mobile='$mobile',
                      city='$city', state='$state', status='$status' WHERE id=$edit_id";
            }
            mysqli_query($link, $q);
            $success = 'Distributor update ho gaya!';
        } else {
            if($uname !== '' && $rawpass !== ''){
                $pass = mysqli_real_escape_string($link, password_hash($rawpass, PASSWORD_DEFAULT));
                $q = "INSERT INTO distributors (distributor_name, mobile, city, state, status, username, password)
                      VALUES ('$name','$mobile','$city','$state','$status','$uname','$pass')";
            } else {
                $q = "INSERT INTO distributors (distributor_name, mobile, city, state, status)
                      VALUES ('$name','$mobile','$city','$state','$status')";
            }
            mysqli_query($link, $q);
            $success = 'Distributor add ho gaya!';
        }
    }
}

// ── Reset Password ─────────────────────────────────────────────────────────
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])){
    $rpid   = (int)$_POST['reset_dist_id'];
    $newpw  = trim($_POST['new_password'] ?? '');
    $confpw = trim($_POST['confirm_password'] ?? '');
    if($newpw === ''){
        $error = 'Naya password blank nahi ho sakta!';
    } elseif($newpw !== $confpw){
        $error = 'Dono passwords match nahi karte!';
    } else {
        $hashed = mysqli_real_escape_string($link, password_hash($newpw, PASSWORD_DEFAULT));
        mysqli_query($link, "UPDATE distributors SET password='$hashed' WHERE id=$rpid");
        $success = 'Password reset ho gaya!';
    }
}

// ── Delete ─────────────────────────────────────────────────────────────────
if(isset($_GET['delete'])){
    $del = (int)$_GET['delete'];
    mysqli_query($link, "DELETE FROM distributor_macs WHERE distributor_id=$del");
    mysqli_query($link, "DELETE FROM distributors WHERE id=$del");
    header("location:distributor.php"); exit();
}

// ── Edit prefill ───────────────────────────────────────────────────────────
$edit_row = null;
if(isset($_GET['edit'])){
    $eid = (int)$_GET['edit'];
    $edit_row = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM distributors WHERE id=$eid"));
}

// ── Assign MACs panel data ─────────────────────────────────────────────────
$show_assign = isset($_GET['assign']) ? (int)$_GET['assign'] : (isset($show_assign) ? $show_assign : 0);
$assign_dist   = null;
$all_macs      = [];
$assigned_macs = [];
if($show_assign > 0){
    $assign_dist = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM distributors WHERE id=$show_assign"));
    $mr = mysqli_query($link, "SELECT macid, name, status FROM map ORDER BY name ASC");
    while($m = mysqli_fetch_assoc($mr)) $all_macs[] = $m;
    $ar = mysqli_query($link, "SELECT macid FROM distributor_macs WHERE distributor_id=$show_assign");
    while($a = mysqli_fetch_assoc($ar)) $assigned_macs[] = $a['macid'];
}

$records = mysqli_query($link, "SELECT * FROM distributors ORDER BY id DESC");
$total   = mysqli_num_rows($records);
$active  = mysqli_fetch_assoc(mysqli_query($link,"SELECT COUNT(*) as c FROM distributors WHERE status='active'"))['c'];
?>

<div class="content" style="min-height:610px;">
<div class="animated fadeIn">
<div class="container-fluid">

    <!-- Stats -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-white bg-primary text-center py-3">
                <h3><?= $total ?></h3><p class="mb-0">Total Distributors</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success text-center py-3">
                <h3><?= $active ?></h3><p class="mb-0">Active</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning text-center py-3">
                <h3><?= $total - $active ?></h3><p class="mb-0">Inactive</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info text-center py-3" style="cursor:pointer;" onclick="window.open('distributor_login.php','_blank')">
                <h3><i class="fa fa-external-link" style="font-size:20px;line-height:1.5;"></i></h3>
                <p class="mb-0">Distributor Portal</p>
            </div>
        </div>
    </div>

    <?php if($show_assign > 0 && $assign_dist): ?>
    <!-- ── MAC Assignment Panel ── -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <strong><i class="fa fa-desktop"></i> MAC Assign Karo &mdash; <?= htmlspecialchars($assign_dist['distributor_name']) ?></strong>
                    <a href="distributor.php" class="btn btn-sm btn-light"><i class="fa fa-times"></i> Close</a>
                </div>
                <div class="card-body">
                    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="assign_macs" value="1">
                        <input type="hidden" name="assign_dist_id" value="<?= $show_assign ?>">
                        <?php if(count($all_macs) === 0): ?>
                            <p class="text-muted">Map table mein koi MAC nahi hai abhi tak.</p>
                        <?php else: ?>
                            <p class="text-muted mb-3" style="font-size:13px;"><i class="fa fa-info-circle"></i> Jo MACs check karein woh is distributor ke ho jaayenge. Uncheck karne pe hata diya jaayega.</p>
                            <div class="row">
                            <?php foreach($all_macs as $m): ?>
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input"
                                               id="mac_<?= htmlspecialchars($m['macid']) ?>"
                                               name="macs[]"
                                               value="<?= htmlspecialchars($m['macid']) ?>"
                                               <?= in_array($m['macid'], $assigned_macs) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="mac_<?= htmlspecialchars($m['macid']) ?>">
                                            <strong><?= htmlspecialchars($m['name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($m['macid']) ?></small>
                                            <span class="badge badge-<?= $m['status']==='ACTIVE' ? 'success' : 'danger' ?> ml-1" style="font-size:10px;"><?= $m['status'] ?></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            <hr>
                            <button type="submit" class="btn btn-info">
                                <i class="fa fa-save"></i> Save Assignment
                            </button>
                            <a href="distributor.php" class="btn btn-secondary ml-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <strong><i class="fa fa-<?= $edit_row ? 'edit' : 'plus' ?>"></i>
                    <?= $edit_row ? 'Edit Distributor' : 'Add Distributor' ?></strong>
                    <?php if($edit_row): ?>
                        <a href="distributor.php" class="btn btn-sm btn-secondary float-right">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if($success && !$show_assign): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                    <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="edit_id" value="<?= $edit_row ? $edit_row['id'] : 0 ?>">
                        <div class="form-group">
                            <label><b>Distributor Name *</b></label>
                            <input type="text" name="distributor_name" class="form-control" required
                                   placeholder="Distributor ka naam"
                                   value="<?= $edit_row ? htmlspecialchars($edit_row['distributor_name']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label><b>Mobile *</b></label>
                            <input type="text" name="mobile" class="form-control" required maxlength="15"
                                   placeholder="Mobile number"
                                   value="<?= $edit_row ? htmlspecialchars($edit_row['mobile']) : '' ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label><b>City</b></label>
                                <input type="text" name="city" class="form-control"
                                       placeholder="City"
                                       value="<?= $edit_row ? htmlspecialchars($edit_row['city']) : '' ?>">
                            </div>
                            <div class="form-group col-6">
                                <label><b>State</b></label>
                                <input type="text" name="state" class="form-control"
                                       placeholder="State"
                                       value="<?= $edit_row ? htmlspecialchars($edit_row['state']) : '' ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><b>Status</b></label>
                            <select name="status" class="form-control">
                                <option value="active" <?= ($edit_row && $edit_row['status']=='active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($edit_row && $edit_row['status']=='inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <hr>
                        <p class="text-muted mb-2" style="font-size:12px;"><i class="fa fa-lock"></i> Portal Login Credentials</p>

                        <div class="form-group">
                            <label><b>Username</b></label>
                            <input type="text" name="username" class="form-control" autocomplete="off"
                                   placeholder="Login username"
                                   value="<?= $edit_row ? htmlspecialchars($edit_row['username'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>
                                <b>Password</b>
                                <?php if($edit_row): ?>
                                    <small class="text-muted font-weight-normal">(blank = change mat karo)</small>
                                <?php endif; ?>
                            </label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password"
                                   placeholder="<?= $edit_row ? 'Naya password (optional)' : 'Password set karo' ?>">
                        </div>

                        <button type="submit" class="btn btn-<?= $edit_row ? 'warning' : 'success' ?> btn-block">
                            <i class="fa fa-<?= $edit_row ? 'save' : 'plus' ?>"></i>
                            <?= $edit_row ? 'Update Karo' : 'Add Karo' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <strong><i class="fa fa-list"></i> Distributors List</strong>
                    <span class="badge badge-primary float-right mt-1"><?= $total ?> Total</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>City/State</th>
                                <th>Login</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 1;
                        mysqli_data_seek($records, 0);
                        while($row = mysqli_fetch_assoc($records)):
                            $mac_count = mysqli_fetch_assoc(mysqli_query($link,
                                "SELECT COUNT(*) as c FROM distributor_macs WHERE distributor_id={$row['id']}"))['c'];
                        ?>
                            <tr <?= ($edit_row && $edit_row['id']==$row['id']) ? 'class="table-warning"' : '' ?>>
                                <td><?= $i++ ?></td>
                                <td><b><?= htmlspecialchars($row['distributor_name']) ?></b></td>
                                <td><?= htmlspecialchars($row['mobile']) ?></td>
                                <td><?= htmlspecialchars($row['city']) ?><?= $row['state'] ? ', '.$row['state'] : '' ?></td>
                                <td>
                                    <?php if(!empty($row['username'])): ?>
                                        <span class="badge badge-success"><i class="fa fa-check"></i> Set</span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($row['username']) ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['status']=='active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <a href="distributor.php?assign=<?= $row['id'] ?>"
                                       class="btn btn-info btn-sm mb-1" title="MACs Assign Karo">
                                        <i class="fa fa-desktop"></i>
                                        <span class="badge badge-light ml-1"><?= $mac_count ?></span>
                                    </a>
                                    <a href="distributor.php?edit=<?= $row['id'] ?>"
                                       class="btn btn-warning btn-sm mb-1"><i class="fa fa-edit"></i></a>
                                    <button type="button"
                                            class="btn btn-secondary btn-sm mb-1"
                                            title="Password Reset Karo"
                                            onclick="openResetModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['distributor_name'])) ?>')">
                                        <i class="fa fa-key"></i>
                                    </button>
                                    <a href="distributor.php?delete=<?= $row['id'] ?>"
                                       class="btn btn-danger btn-sm mb-1"
                                       onclick="return confirm('Delete karein? Assigned MACs bhi remove honge.')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if($total == 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">Koi distributor nahi hai abhi tak.</td></tr>
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
</div>

<!-- ── Reset Password Modal ─────────────────────────────────────────────── -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="resetPasswordModalLabel">
                    <i class="fa fa-key"></i> Password Reset
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" id="resetPasswordForm" onsubmit="return validateResetForm()">
                <input type="hidden" name="reset_password" value="1">
                <input type="hidden" name="reset_dist_id" id="resetDistId" value="">
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:13px;">
                        <i class="fa fa-user"></i> <strong id="resetDistName"></strong> ka password reset ho jaayega.
                    </p>
                    <div class="form-group">
                        <label><b>Naya Password *</b></label>
                        <input type="password" name="new_password" id="newPassword" class="form-control" required placeholder="Naya password likho">
                    </div>
                    <div class="form-group">
                        <label><b>Confirm Password *</b></label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required placeholder="Dobara likho">
                        <small id="pwMatchMsg" class="text-danger" style="display:none;">Passwords match nahi kar rahe!</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-key"></i> Reset Karo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openResetModal(id, name) {
    document.getElementById('resetDistId').value = id;
    document.getElementById('resetDistName').textContent = name;
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('pwMatchMsg').style.display = 'none';
    $('#resetPasswordModal').modal('show');
}

function validateResetForm() {
    var np = document.getElementById('newPassword').value;
    var cp = document.getElementById('confirmPassword').value;
    if (np !== cp) {
        document.getElementById('pwMatchMsg').style.display = 'block';
        return false;
    }
    document.getElementById('pwMatchMsg').style.display = 'none';
    return true;
}

document.getElementById('confirmPassword').addEventListener('input', function() {
    var np = document.getElementById('newPassword').value;
    var msg = document.getElementById('pwMatchMsg');
    if (this.value && this.value !== np) {
        msg.style.display = 'block';
    } else {
        msg.style.display = 'none';
    }
});
</script>

<?php include('layout/footer.php'); ?>
