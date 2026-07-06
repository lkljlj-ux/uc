<?php
// Upload endpoint for EcmpCollector JAR
// JAR sends: POST multipart/form-data with fields: file, hostname, username, ipAddress, ecmpPath, fileCount, originalName
mysqli_report(MYSQLI_REPORT_OFF);

$collections_dir = __DIR__ . '/collections/';
if(!is_dir($collections_dir)) mkdir($collections_dir, 0755, true);

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit();
}

if(empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK){
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error', 'code' => $_FILES['file']['error'] ?? -1]);
    exit();
}

$orig_name = basename($_FILES['file']['name'] ?? 'collection.zip');
$id        = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
$dest      = $collections_dir . $id . '.zip';

if(!move_uploaded_file($_FILES['file']['tmp_name'], $dest)){
    http_response_code(500);
    echo json_encode(['error' => 'File save failed']);
    exit();
}

$file_count = (int)($_POST['fileCount'] ?? 0);
if($file_count === 0){
    try {
        $zip = new ZipArchive();
        if($zip->open($dest) === true){ $file_count = $zip->numFiles; $zip->close(); }
    } catch(Exception $e){}
}

$meta = [
    'id'           => $id,
    'originalName' => $orig_name,
    'uploadedAt'   => date('c'),
    'hostname'     => $_POST['hostname']  ?? '',
    'username'     => $_POST['username']  ?? '',
    'ipAddress'    => $_POST['ipAddress'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
    'ecmpPath'     => $_POST['ecmpPath']  ?? '',
    'fileCount'    => $file_count,
];
file_put_contents($dest . '.meta.json', json_encode($meta, JSON_PRETTY_PRINT));

echo json_encode(['ok' => true, 'id' => $id, 'message' => 'Upload successful']);
