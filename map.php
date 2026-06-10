<?php
$pages = 'test2';
include('layout/header.php');
if(!isset($_SESSION['user_token']) || $_SESSION['user_type'] != 'admin'){
    header("location:/login.php");
    exit();
}

$success = '';
$error   = '';

// XML Download
if(isset($_GET['xml'])){
    $rows = [];
    $q = mysqli_query($link, "SELECT * FROM map ORDER BY id ASC");
    while($r = mysqli_fetch_assoc($q)) $rows[] = $r;

    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="map_data_' . date('Y-m-d') . '.xml"');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo "<map_data>\n";
    foreach($rows as $r){
        echo "  <record>\n";
        echo "    <id>" . htmlspecialchars($r['id']) . "</id>\n";
        echo "    <name>" . htmlspecialchars($r['name']) . "</name>\n";
        echo "    <macid>" . htmlspecialchars($r['macid']) . "</macid>\n";
        echo "    <status>" . htmlspecialchars($r['status']) . "</status>\n";
        echo "  </record>\n";
    }
    echo "</map_data>";
    exit();
}

// Add
if(isset($_POST['save'])){
    $name   = mysqli_real_escape_string($link, trim($_POST['name']));
    $macid  = mysqli_real_escape_string($link, trim($_POST['machine']));
    $status = mysqli_real_escape_string($link, $_POST['user_name']);
    if($name === '' || $macid === ''){
        $error = 'Name aur MAC ID zaroori hain.';
    } else {
        mysqli_query($link, "INSERT INTO map (name, macid, status) VALUES ('$name','$macid','$status')");
        $success = 'Record add ho gaya!';
    }
}

// Update (Edit submit)
if(isset($_POST['update'])){
    $id     = (int)$_POST['edit_id'];
    $name   = mysqli_real_escape_string($link, trim($_POST['name']));
    $macid  = mysqli_real_escape_string($link, trim($_POST['machine']));
    $status = mysqli_real_escape_string($link, $_POST['user_name']);
    if($name === '' || $macid === ''){
        $error = 'Name aur MAC ID zaroori hain.';
    } else {
        mysqli_query($link, "UPDATE map SET name='$name', macid='$macid', status='$status' WHERE id=$id");
        $success = 'Record update ho gaya!';
        header("location:map.php?updated=1");
        exit();
    }
}

// Delete by ID
if(isset($_GET['delete'])){
    $del_id = (int)$_GET['delete'];
    mysqli_query($link, "DELETE FROM map WHERE id=$del_id");
    header("location:map.php");
    exit();
}

// Edit: pre-fill
$edit_row = null;
if(isset($_GET['edit'])){
    $eid = (int)$_GET['edit'];
    $er  = mysqli_query($link, "SELECT * FROM map WHERE id=$eid");
    $edit_row = mysqli_fetch_assoc($er);
}

if(isset($_GET['updated'])) $success = 'Record update ho gaya!';

// All records
$query = mysqli_query($link, "SELECT * FROM map ORDER BY id DESC");
$col   = 0;
?>

<link rel="stylesheet" href="assets/css/lib/datatable/dataTables.bootstrap.min.css">

<div class="content" style="min-height:610px;">
    <div class="animated fadeIn">

        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible mx-3 mt-3">
                <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible mx-3 mt-3">
                <?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Add / Edit Form -->
        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <strong><?php echo $edit_row ? '<i class="fa fa-edit"></i> Record Edit' : '<i class="fa fa-plus"></i> New Record Add'; ?></strong>
                        <?php if($edit_row): ?>
                            <a href="map.php" class="btn btn-sm btn-secondary float-right">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="map.php">
                            <?php if($edit_row): ?>
                                <input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label><b>Name</b></label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="Name likhein"
                                    value="<?php echo $edit_row ? htmlspecialchars($edit_row['name']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label><b>MAC ID</b></label>
                                <input type="text" name="machine" class="form-control"
                                    placeholder="MAC ID likhein"
                                    value="<?php echo $edit_row ? htmlspecialchars($edit_row['macid']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label><b>Status</b></label>
                                <select name="user_name" class="form-control">
                                    <option value="ACTIVE"  <?php echo ($edit_row && $edit_row['status']=='ACTIVE')  ? 'selected' : ''; ?>>ACTIVE</option>
                                    <option value="INACTIVE"<?php echo ($edit_row && $edit_row['status']=='INACTIVE') ? 'selected' : ''; ?>>INACTIVE</option>
                                </select>
                            </div>
                            <?php if($edit_row): ?>
                                <button type="submit" name="update" class="btn btn-warning btn-block">
                                    <i class="fa fa-save"></i> Update Karo
                                </button>
                            <?php else: ?>
                                <button type="submit" name="save" class="btn btn-success btn-block">
                                    <i class="fa fa-plus"></i> Save Karo
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <strong class="card-title">All Map Data</strong>
                        <a href="map.php?xml=1" class="btn btn-sm btn-primary float-right">
                            <i class="fa fa-download"></i> Download XML
                        </a>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-hover" id="bootstrap-data-table-export">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>MAC ID</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while($data = mysqli_fetch_assoc($query)): $col++; ?>
                                <tr <?php echo ($edit_row && $edit_row['id']==$data['id']) ? 'class="table-warning"' : ''; ?>>
                                    <td><?php echo $col; ?></td>
                                    <td><?php echo htmlspecialchars($data['name']); ?></td>
                                    <td><?php echo htmlspecialchars($data['macid']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $data['status']=='ACTIVE' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $data['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="map.php?edit=<?php echo $data['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <a href="map.php?delete=<?php echo $data['id']; ?>"
                                           class="btn btn-danger btn-sm ml-1"
                                           onclick="return confirm('Is record ko delete karein?')">
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
</div>

<script src="assets/js/lib/data-table/datatables.min.js"></script>
<script src="assets/js/lib/data-table/dataTables.bootstrap.min.js"></script>
<script src="assets/js/lib/data-table/dataTables.buttons.min.js"></script>
<script src="assets/js/lib/data-table/buttons.bootstrap.min.js"></script>
<script src="assets/js/lib/data-table/jszip.min.js"></script>
<script src="assets/js/lib/data-table/vfs_fonts.js"></script>
<script src="assets/js/lib/data-table/buttons.html5.min.js"></script>
<script src="assets/js/lib/data-table/buttons.print.min.js"></script>
<script src="assets/js/lib/data-table/buttons.colVis.min.js"></script>
<script src="assets/js/init/datatables-init.js"></script>

<?php include('layout/footer.php'); ?>
