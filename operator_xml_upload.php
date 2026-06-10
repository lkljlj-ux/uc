<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:login.php");
    exit();
}

$success = '';
$error   = '';

if(isset($_POST['upload'])){
    $aadhar = preg_replace('/[^0-9]/', '', trim($_POST['aadhar_no']));

    if(empty($aadhar) || strlen($aadhar) < 12){
        $error = "Valid 12-digit Aadhaar number daalo!";
    } elseif(!isset($_FILES['xml_file']) || $_FILES['xml_file']['error'] !== 0){
        $error = "XML file select karo!";
    } else {
        $file = $_FILES['xml_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if($ext !== 'xml'){
            $error = "Sirf XML file upload allowed hai!";
        } else {
            $content = file_get_contents($file['tmp_name']);
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            if($xml === false){
                $error = "Invalid XML format! Sahi XML file upload karo.";
            } else {
                $savePath = __DIR__ . '/xml_uploads/' . $aadhar . '.xml';
                if(move_uploaded_file($file['tmp_name'], $savePath)){
                    $relPath   = 'xml_uploads/' . $aadhar . '.xml';
                    $origName  = mysqli_real_escape_string($link, $file['name']);
                    $fileSize  = filesize($savePath);
                    $uploadedBy = mysqli_real_escape_string($link, $_SESSION['user_name'] ?? 'admin');

                    // Upsert: agar same aadhar pehle se hai to update karo
                    $chk = mysqli_query($link, "SELECT id FROM xml_uploads WHERE aadhar_no='$aadhar'");
                    if(mysqli_num_rows($chk) > 0){
                        mysqli_query($link, "UPDATE xml_uploads SET file_path='$relPath', original_name='$origName', file_size=$fileSize, uploaded_by='$uploadedBy', created_at=NOW() WHERE aadhar_no='$aadhar'");
                        $success = "XML file update ho gayi! Aadhaar: <b>$aadhar</b>";
                    } else {
                        mysqli_query($link, "INSERT INTO xml_uploads (aadhar_no, file_path, original_name, file_size, uploaded_by) VALUES ('$aadhar','$relPath','$origName',$fileSize,'$uploadedBy')");
                        $success = "XML file successfully upload ho gayi! Aadhaar: <b>$aadhar</b>";
                    }
                } else {
                    $error = "File save nahi ho payi, dobara try karo.";
                }
            }
        }
    }
}

// Delete
if(isset($_GET['delete'])){
    $del_id = (int)$_GET['delete'];
    $row = mysqli_fetch_assoc(mysqli_query($link, "SELECT aadhar_no FROM xml_uploads WHERE id=$del_id"));
    if($row){
        $fp = __DIR__ . '/xml_uploads/' . $row['aadhar_no'] . '.xml';
        if(file_exists($fp)) unlink($fp);
        mysqli_query($link, "DELETE FROM xml_uploads WHERE id=$del_id");
    }
    header("location:operator_xml_upload.php");
    exit();
}

$records = mysqli_query($link, "SELECT * FROM xml_uploads ORDER BY id DESC");
?>

<div class="content" style="min-height:610px;">
<div class="animated fadeIn">
<div class="container-fluid">
<div class="row">

    <!-- Upload Form -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><strong><i class="fa fa-upload"></i> Operator XML Upload</strong></div>
            <div class="card-body">
                <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><b>Aadhaar Number</b></label>
                        <input type="text" name="aadhar_no" class="form-control"
                               placeholder="12-digit Aadhaar number" maxlength="12" pattern="[0-9]{12}" required
                               value="<?= isset($_POST['aadhar_no']) ? htmlspecialchars($_POST['aadhar_no']) : '' ?>">
                        <small class="text-muted">Sirf 12 digits daalo</small>
                    </div>
                    <div class="form-group">
                        <label><b>XML File</b></label>
                        <input type="file" name="xml_file" class="form-control-file" accept=".xml" required>
                        <small class="text-muted">Sirf .xml format allowed hai</small>
                    </div>
                    <button type="submit" name="upload" class="btn btn-primary btn-block">
                        <i class="fa fa-upload"></i> Upload XML
                    </button>
                </form>

                <hr>
                <div class="alert alert-info mb-0">
                    <b>Download API:</b><br>
                    <code>downloadXML.php?userId=<i>aadhaar_no</i></code><br>
                    <small>File <b>pdf.xml</b> naam se download hogi</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Records List -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><strong><i class="fa fa-database"></i> Uploaded XML Records</strong></div>
            <div class="card-body">
                <?php if(mysqli_num_rows($records) == 0): ?>
                    <p class="text-muted text-center">Koi record nahi hai abhi tak.</p>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Aadhaar No.</th>
                            <th>Uploaded By</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i=1; while($row = mysqli_fetch_assoc($records)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><b><?= htmlspecialchars($row['aadhar_no']) ?></b></td>
                            <td><?= htmlspecialchars($row['uploaded_by']) ?></td>
                            <td><?= round($row['file_size']/1024, 1) ?> KB</td>
                            <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="downloadXML.php?userId=<?= urlencode($row['aadhar_no']) ?>"
                                   class="btn btn-sm btn-success"><i class="fa fa-download"></i> pdf.xml</a>
                                <a href="operator_xml_upload.php?delete=<?= $row['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete karein?')"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
</div>
</div>
</div>

<?php include('layout/footer.php'); ?>
