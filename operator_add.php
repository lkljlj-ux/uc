<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:/login.php");
    exit();
}

$success = '';
$error   = '';

// Handle POST (Add or Edit)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aadhar_no'])){
    $edit_id       = (int)($_POST['edit_id'] ?? 0);
    $operator_name = mysqli_real_escape_string($link, trim($_POST['operator_name']));
    $aadhar_no     = mysqli_real_escape_string($link, trim($_POST['aadhar_no']));
    $token_no      = mysqli_real_escape_string($link, trim($_POST['token_no']));
    $session_data  = mysqli_real_escape_string($link, trim($_POST['session_data']));
    $start_time    = mysqli_real_escape_string($link, trim($_POST['start_time']));

    if($operator_name === '' || $aadhar_no === '' || $token_no === '' || $start_time === ''){
        $error = 'Operator Name, Aadhar No., Token No. aur Start Time zaroori hain.';
    } elseif(strlen($token_no) > 6){
        $error = 'Token No. 6 digit se zyada nahi hona chahiye.';
    } else {
        if($edit_id > 0){
            $query = "UPDATE test SET 
                user_b='$operator_name', user_id='$aadhar_no', user_token='$token_no',
                user_s2='$session_data', startTime='$start_time'
                WHERE id=$edit_id";
            if(mysqli_query($link, $query)){
                $success = 'Operator successfully update ho gaya!';
            } else {
                $error = 'Error: ' . mysqli_error($link);
            }
        } else {
            $query = "INSERT INTO test 
                (user_id, user_token, user_b, user_s, user_s2, stationType, startTime, expiryTime, registrationSequenceNumber, status, macid)
                VALUES 
                ('$aadhar_no', '$token_no', '$operator_name', '', '$session_data', '', '$start_time', '', '', '', '')";
            if(mysqli_query($link, $query)){
                $success = 'Operator successfully add ho gaya!';
            } else {
                $error = 'Error: ' . mysqli_error($link);
            }
        }
    }
}

// Delete
if(isset($_GET['delete'])){
    $del_id = (int)$_GET['delete'];
    mysqli_query($link, "DELETE FROM test WHERE id=$del_id");
    header("location:operator_add.php");
    exit();
}

// Edit: pre-fill form
$edit_row = null;
if(isset($_GET['edit'])){
    $eid = (int)$_GET['edit'];
    $er  = mysqli_query($link, "SELECT * FROM test WHERE id=$eid");
    $edit_row = mysqli_fetch_assoc($er);
}

// Fetch all operators
$result = mysqli_query($link, "SELECT * FROM test ORDER BY id DESC");
?>

        <!-- Content -->
        <div class="content" style="min-height: 610px;">
            <div class="animated fadeIn">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <strong><?php echo $edit_row ? '<i class="fa fa-edit"></i> Operator Edit' : '<i class="fa fa-plus"></i> Operator Add'; ?></strong>
                                <?php if($edit_row): ?>
                                    <a href="operator_add.php" class="btn btn-sm btn-secondary float-right">
                                        <i class="fa fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">

                                <?php if($success): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="operator_add.php">
                                    <input type="hidden" name="edit_id" value="<?php echo $edit_row ? $edit_row['id'] : 0; ?>">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label><b>Operator Name</b></label>
                                            <input type="text" name="operator_name" class="form-control"
                                                placeholder="Operator ka naam"
                                                value="<?php echo $edit_row ? htmlspecialchars($edit_row['user_b']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label><b>Aadhar No.</b></label>
                                            <input type="text" name="aadhar_no" class="form-control"
                                                placeholder="12 digit Aadhar number" maxlength="12"
                                                value="<?php echo $edit_row ? htmlspecialchars($edit_row['user_id']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label><b>Token No.</b></label>
                                            <input type="text" name="token_no" class="form-control"
                                                placeholder="Max 6 digit" maxlength="6"
                                                value="<?php echo $edit_row ? htmlspecialchars($edit_row['user_token']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label><b>Start Time</b></label>
                                            <input type="text" name="start_time" class="form-control"
                                                placeholder="Start time likhein"
                                                value="<?php echo $edit_row ? htmlspecialchars($edit_row['startTime']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><b>Session Data</b></label>
                                        <textarea name="session_data" class="form-control" rows="3"
                                            placeholder="Session data (optional)"><?php echo $edit_row ? htmlspecialchars($edit_row['user_s2']) : ''; ?></textarea>
                                    </div>
                                    <?php if($edit_row): ?>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fa fa-save"></i> Update Karo
                                        </button>
                                        <a href="operator_add.php" class="btn btn-secondary ml-2">Cancel</a>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-plus"></i> Operator Add Karo
                                        </button>
                                    <?php endif; ?>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operators List -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Operators List</strong>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Operator Name</th>
                                                <th>Aadhar No.</th>
                                                <th>Token No.</th>
                                                <th>Session Data</th>
                                                <th>Start Time</th>
                                                <th>Added On</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $sr = 1;
                                        while($row = mysqli_fetch_assoc($result)):
                                        ?>
                                            <tr <?php echo ($edit_row && $edit_row['id']==$row['id']) ? 'class="table-warning"' : ''; ?>>
                                                <td><?php echo $sr++; ?></td>
                                                <td><?php echo htmlspecialchars($row['user_b']); ?></td>
                                                <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['user_token']); ?></td>
                                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                                    title="<?php echo htmlspecialchars($row['user_s2']); ?>">
                                                    <?php echo htmlspecialchars($row['user_s2']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['startTime']); ?></td>
                                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                <td>
                                                    <a href="operator_add.php?edit=<?php echo $row['id']; ?>"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <a href="operator_add.php?delete=<?php echo $row['id']; ?>"
                                                       class="btn btn-danger btn-sm ml-1"
                                                       onclick="return confirm('Is operator ko delete karein?')">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- .animated -->
        </div><!-- /.content -->

<?php include('layout/footer.php'); ?>
