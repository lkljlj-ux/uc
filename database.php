<?php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);
$dbName = getenv('DB_NAME') ?: 'aadhaar_test';
$dbUser = getenv('DB_USER') ?: 'aadhaar_test';
$dbPass = getenv('DB_PASSWORD');

// Local development compatibility. Production must always set DB_PASSWORD.
if($dbPass === false){
    $dbPass = 'ftYI6.B#s2K&';
}

$link = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if(!$link){
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Database connection unavailable.');
}
mysqli_set_charset($link, 'utf8mb4');