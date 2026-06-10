<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:/login.php");
    exit();
}

$success = '';
$error   = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aadhar_no'])){
    $operator_name = mysqli_real_escape_string($link, trim($_POST['operator_name']));
    $aadhar_no     = mysqli_real_escape_string($link, trim($_POST['aadhar_no']));
    $token_no      = mysqli_real_escape_string($link, trim($_POST['token_no']));
    $session_data  = mysqli_real_escape_string($link, trim($_POST['session_data']));
    $start_time    = mysqli_real_escape_string($link, trim($_POST['start_time']));

    if($operator_name === '' || $aadhar_no === '' || $token_no === '' || $start_time === ''){
        $error = 'Operator Name, Aadhar No., Token No. aur Start Time zaroori hain.';
    } else {
        $insert = "INSERT INTO test 
            (user_id, user_token, user_b, user_s, user_s2, stationType, startTime, expiryTime, registrationSequenceNumber, status, macid)
            VALUES 
            ('$aadhar_no', '$token_no', '$operator_name', '', '$session_data', '', '$start_time', '', '', '', '')";
        if(mysqli_query($link, $insert)){
            $success = 'Operator successfully add ho gaya!';
        } else {
            $error = 'Error: ' . mysqli_error($link);
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
                                <strong>Operator Add</strong>
                            </div>
                            <div class="card-body">

                                <?php if($success): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="operator_add.php">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label><b>Operator Name</b></label>
                                            <input type="text" name="operator_name" class="form-control" placeholder="Operator ka naam" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label><b>Aadhar No.</b></label>
                                            <input type="text" name="aadhar_no" class="form-control" placeholder="12 digit Aadhar number" maxlength="12" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label><b>Token No.</b></label>
                                            <input type="text" name="token_no" class="form-control" placeholder="Token number" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label><b>Start Time</b></label>
                                            <input type="datetime-local" name="start_time" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><b>Session Data</b></label>
                                        <textarea name="session_data" class="form-control" rows="3" placeholder="Session data (optional)"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-plus"></i> Operator Add Karo
                                    </button>
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
                                            <tr>
                                                <td><?php echo $sr++; ?></td>
                                                <td><?php echo htmlspecialchars($row['user_b']); ?></td>
                                                <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['user_token']); ?></td>
                                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($row['user_s2']); ?>">
                                                    <?php echo htmlspecialchars($row['user_s2']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['startTime']); ?></td>
                                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                <td>
                                                    <a href="operator_add.php?delete=<?php echo $row['id']; ?>"
                                                       class="btn btn-danger btn-sm"
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
