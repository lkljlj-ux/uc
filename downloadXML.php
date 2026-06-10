<?php
if(!isset($_GET['userId']) || empty(trim($_GET['userId']))){
    http_response_code(400);
    echo "Error: userId parameter required. Example: downloadXML.php?userId=123456789012";
    exit();
}

$userId  = preg_replace('/[^0-9a-zA-Z_\-]/', '', trim($_GET['userId']));
$xmlPath = __DIR__ . '/xml_uploads/' . $userId . '.xml';

if(!file_exists($xmlPath)){
    http_response_code(404);
    echo "Error: userId '$userId' ke liye koi XML file nahi mili.";
    exit();
}

header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="pdf.xml"');
header('Content-Length: ' . filesize($xmlPath));
header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache');

readfile($xmlPath);
exit();
