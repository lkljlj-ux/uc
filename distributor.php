<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:login.php");
    exit();
}

$success = '';
$error   = '';

// Add / Edit
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['distributor_name'])){
    $edit_id   = (int)($_POST['edit_id'] ?? 0);
    $name      = mysqli_real_escape_string($link, trim($_POST['distributor_name']));
    $mobile    = mysqli_real_escape_string($link, trim($_POST['mobile']));
    $email     = mysqli_real_escape_string($link, trim($_POST['email']));
    $address   = mysqli_real_escape_string($link, trim($_POST['address']));
    $city      = mysqli_real_escape_string($link, trim($_POST['city']));
    $state     = mysqli_real_escape_string($link, trim($_POST['state']));
    $pincode   = mysqli_real_escape_string($link, trim($_POST['pincode']));
    $status    = ($_POST['status'] === 'active') ? 'active' : 'inactive';

    if($name === '' || $mobile === ''){
        $error = 'Distributor Name aur Mobile zaroori hain!';
    } else {
        if($edit_id > 0){
            $q = "UPDATE distributors SET distributor_name='$name', mobile='$mobile', email='$email',
                  address='$address', city='$city', state='$state', pincode='$pincode', status='$status'
                  WHERE id=$edit_id";
            mysqli_query($link, $q);
            $success = 'Distributor update ho gaya!';
        } else {
            $q = "INSERT INTO distributors (distributor_name, mobile, email, address, city, state, pincode, status)
                  VALUES ('$name','$mobile','$email','$address','$city','$state','$pincode','$status')";
            mysqli_query($link, $q);
            $success = 'Distributor add ho gaya!';
        }
    }
}

// Delete
if(isset($_GET['delete'])){
    $del = (int)$_GET['delete'];
    mysqli_query($link, "DELETE FROM distributors WHERE id=$del");
    header("location:distributor.php"); exit();
}

// Edit prefill
$edit_row = null;
if(isset($_GET['edit'])){
    $eid = (int)$_GET['edit'];
    $edit_row = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM distributors WHERE id=$eid"));
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
    </div>

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
                    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
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
                        <div class="form-group">
                            <label><b>Email</b></label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="Email address"
                                   value="<?= $edit_row ? htmlspecialchars($edit_row['email']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label><b>Address</b></label>
                            <textarea name="address" class="form-control" rows="2"
                                placeholder="Pura address"><?= $edit_row ? htmlspecialchars($edit_row['address']) : '' ?></textarea>
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
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label><b>Pincode</b></label>
                                <input type="text" name="pincode" class="form-control" maxlength="6"
                                       placeholder="Pincode"
                                       value="<?= $edit_row ? htmlspecialchars($edit_row['pincode']) : '' ?>">
                            </div>
                            <div class="form-group col-6">
                                <label><b>Status</b></label>
                                <select name="status" class="form-control">
                                    <option value="active" <?= ($edit_row && $edit_row['status']=='active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($edit_row && $edit_row['status']=='inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
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
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 1;
                        mysqli_data_seek($records, 0);
                        while($row = mysqli_fetch_assoc($records)):
                        ?>
                            <tr <?= ($edit_row && $edit_row['id']==$row['id']) ? 'class="table-warning"' : '' ?>>
                                <td><?= $i++ ?></td>
                                <td><b><?= htmlspecialchars($row['distributor_name']) ?></b><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small></td>
                                <td><?= htmlspecialchars($row['mobile']) ?></td>
                                <td><?= htmlspecialchars($row['city']) ?><?= $row['state'] ? ', '.$row['state'] : '' ?></td>
                                <td>
                                    <?php if($row['status']=='active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="distributor.php?edit=<?= $row['id'] ?>"
                                       class="btn btn-warning btn-sm"><i class="fa fa-edit"></i></a>
                                    <a href="distributor.php?delete=<?= $row['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete karein?')"><i class="fa fa-trash"></i></a>
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

<?php include('layout/footer.php'); ?>
