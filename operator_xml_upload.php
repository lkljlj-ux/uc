<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:login.php");
    exit();
}

$success = '';
$error   = '';

if(isset($_POST['upload'])){
    $aadhar = trim($_POST['aadhar_no']);
    $aadhar = preg_replace('/[^0-9]/', '', $aadhar);

    if(empty($aadhar) || strlen($aadhar) < 12){
        $error = "Valid 12-digit Aadhaar number daalo!";
    } elseif(!isset($_FILES['xml_file']) || $_FILES['xml_file']['error'] !== 0){
        $error = "XML file select karo!";
    } else {
        $file     = $_FILES['xml_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);

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
                    $success = "XML file successfully upload ho gayi! Aadhaar: <b>$aadhar</b>";
                } else {
                    $error = "File save nahi ho payi, dobara try karo.";
                }
            }
        }
    }
}

// List uploaded files
$uploadedFiles = glob(__DIR__ . '/xml_uploads/*.xml');
?>

        <div class="content" style="min-height:610px;">
            <div class="animated fadeIn">

<div class="container-fluid">
    <div class="row">

        <!-- Upload Form -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <strong class="card-title"><i class="fa fa-upload"></i> Operator XML Upload</strong>
                </div>
                <div class="card-body">

                    <?php if($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label><b>Aadhaar Number</b></label>
                            <input type="text" name="aadhar_no" class="form-control"
                                   placeholder="12-digit Aadhaar number" maxlength="12"
                                   pattern="[0-9]{12}" required
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

        <!-- Uploaded Files List -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <strong class="card-title"><i class="fa fa-list"></i> Uploaded XML Files</strong>
                </div>
                <div class="card-body">
                    <?php if(empty($uploadedFiles)): ?>
                        <p class="text-muted text-center">Koi file upload nahi hui abhi tak.</p>
                    <?php else: ?>
                    <table class="table table-bordered table-striped table-hover" id="xmlTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Aadhaar No.</th>
                                <th>Upload Date</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 1;
                        foreach(array_reverse($uploadedFiles) as $f):
                            $name     = basename($f, '.xml');
                            $date     = date('d-m-Y H:i', filemtime($f));
                            $size     = round(filesize($f) / 1024, 1) . ' KB';
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><b><?= htmlspecialchars($name) ?></b></td>
                                <td><?= $date ?></td>
                                <td><?= $size ?></td>
                                <td>
                                    <a href="downloadXML.php?userId=<?= urlencode($name) ?>"
                                       class="btn btn-sm btn-success">
                                        <i class="fa fa-download"></i> pdf.xml
                                    </a>
                                    <a href="operator_xml_upload.php?delete=<?= urlencode($name) ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete karein?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

            </div>
        </div>

<?php
// Delete handler
if(isset($_GET['delete'])){
    $del = preg_replace('/[^0-9]/', '', $_GET['delete']);
    $delPath = __DIR__ . '/xml_uploads/' . $del . '.xml';
    if(file_exists($delPath)) unlink($delPath);
    header("location:operator_xml_upload.php");
    exit();
}

include('layout/footer.php');
?>
