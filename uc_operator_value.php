<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

function savedValueResponse($status, $body){
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Allow: POST');
    savedValueResponse(405, ['error' => 'POST required hai.']);
}
if(!isset($_SESSION['user_token']) || ($_SESSION['user_type'] ?? '') !== 'admin'){
    savedValueResponse(403, ['error' => 'Access denied.']);
}
if(empty($_SESSION['uc_operator_csrf']) || !is_string($_POST['csrf_token'] ?? null) ||
    !hash_equals($_SESSION['uc_operator_csrf'] ?? '', $_POST['csrf_token'])){
    savedValueResponse(403, ['error' => 'Session expire ho gaya. Page refresh karein.']);
}

$operatorId = filter_var($_POST['operator_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$fields = [
    'aadhaar_no' => 'aadhaar_encrypted',
    'auth_token' => 'auth_token_encrypted',
    'bio_token' => 'bio_token_encrypted',
    'pid_data' => 'pid_data_encrypted',
    'otp' => 'otp_encrypted',
    'verify_otp' => 'verify_otp_encrypted'
];
$field = $_POST['field'] ?? '';
$column = is_string($field) ? ($fields[$field] ?? null) : null;
if($operatorId === false || $column === null){
    savedValueResponse(422, ['error' => 'Valid operator aur field chunein.']);
}

try {
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/includes/uc_security.php';
    $stmt = mysqli_prepare($link, "SELECT $column FROM uc_operators WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $operatorId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if(!$row){
        savedValueResponse(404, ['error' => 'Operator nahi mila.']);
    }
    if($row[$column] === null || $row[$column] === ''){
        savedValueResponse(404, ['error' => 'Saved value nahi hai.']);
    }
    savedValueResponse(200, ['value' => ucDecrypt($row[$column])]);
} catch(Throwable $e){
    savedValueResponse(500, ['error' => 'Saved value read nahi ho saki.']);
}