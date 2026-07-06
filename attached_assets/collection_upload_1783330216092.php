<?php
// collection_upload.php
// EcmpCollector.jar se ZIP file receive karta hai
// Isko apne server par rakho jahan JAR upload bhejta hai

$collections_dir = __DIR__ . '/collections/';
if (!is_dir($collections_dir)) mkdir($collections_dir, 0755, true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit();
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err = isset($_FILES['file']) ? $_FILES['file']['error'] : 'no_file';
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Upload error: ' . $err]);
    exit();
}

$originalName = basename($_FILES['file']['name']);
$tmpPath      = $_FILES['file']['tmp_name'];

// Unique ID generate karo
$id      = date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6);
$destZip = $collections_dir . $id . '.zip';

if (!move_uploaded_file($tmpPath, $destZip)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to save file']);
    exit();
}

// ZIP ke andar se _machine_info.txt padho
$hostname  = '';
$username  = '';
$ecmpPath  = '';
$biosSerial= '';
$fileCount = 0;

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($destZip) === true) {
        $fileCount   = $zip->numFiles;
        $machineInfo = $zip->getFromName('_machine_info.txt');
        if ($machineInfo !== false) {
            foreach (explode("\n", $machineInfo) as $line) {
                $line = trim($line);
                if (preg_match('/^Hostname\s*:\s*(.+)$/i', $line, $m))
                    $hostname = trim($m[1]);
                elseif (preg_match('/^Username\s*:\s*(.+)$/i', $line, $m))
                    $username = trim($m[1]);
                elseif (preg_match('/^ECMP Root\s*:\s*(.+)$/i', $line, $m))
                    $ecmpPath = trim($m[1]);
                elseif (preg_match('/^BIOS Serial\s*:\s*(.+)$/i', $line, $m))
                    $biosSerial = trim($m[1]);
            }
        }
        $zip->close();
    }
}

// Metadata save karo
$meta = [
    'id'           => $id,
    'originalName' => $originalName,
    'uploadedAt'   => date('c'),
    'sizeBytes'    => filesize($destZip),
    'hostname'     => $hostname,
    'username'     => $username,
    'ecmpPath'     => $ecmpPath,
    'biosSerial'   => $biosSerial,
    'ipAddress'    => $_SERVER['REMOTE_ADDR'] ?? '',
    'fileCount'    => $fileCount,
];
file_put_contents($destZip . '.meta.json', json_encode($meta, JSON_PRETTY_PRINT));

echo json_encode(['ok' => true, 'id' => $id, 'message' => 'Upload successful']);
exit();
