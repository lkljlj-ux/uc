<?php
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../includes/uc_security.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(204);
    exit();
}

function ucApiResponse($statusCode, $body){
    http_response_code($statusCode);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

function ucApiHeader($name){
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$serverKey] ?? '';
}

function ucApiIsHttps(){
    return ($_SERVER['HTTPS'] ?? '') === 'on';
}

function ucApiSecurityMode(){
    // Transition must be explicitly configured on an installation with old clients.
    return getenv('UC_API_SECURITY_MODE') === 'transition' ? 'transition' : 'enforce';
}

function ucApiAuthorize($link, $macId){
    if(!ucApiIsHttps()){
        if(ucApiSecurityMode() !== 'transition'){
            ucApiResponse(426, ['status' => false, 'message' => 'HTTPS required']);
        }
        // Legacy HTTP is temporary, but never allow its old unauthenticated bypass.
        $expected = getenv('UC_API_KEY');
        if(!$expected){
            // Existing installations may still use this key until all HTTP clients migrate.
            $expected = getenv('SESSION_SECRET');
        }
        $received = ucApiHeader('X-Auth-Token');
        if(!$expected || $received === '' || !hash_equals($expected, $received)){
            ucApiResponse(401, ['status' => false, 'message' => 'Unauthorized']);
        }
        return false;
    }

    $received = ucApiHeader('X-Auth-Token');
    if(!is_string($received) || !preg_match('/^[a-f0-9]{64}$/D', $received)){
        ucApiResponse(401, ['status' => false, 'message' => 'Unauthorized']);
    }
    $hash = hash('sha256', $received);
    $stmt = mysqli_prepare($link, "SELECT macid FROM uc_device_tokens WHERE token_hash = ? AND revoked_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if(!$row || !hash_equals($row['macid'], $macId)){
        ucApiResponse(401, ['status' => false, 'message' => 'Unauthorized']);
    }
    return true;
}

function ucApiRoute(){
    if(isset($_GET['route'])){
        return trim($_GET['route']);
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    return basename(rtrim($path, '/'));
}

function ucApiMappedOperator($link, $macId){
    $stmt = mysqli_prepare($link, "SELECT
            m.macid, m.status, u.id, u.operator_name, u.aadhaar_last4,
            u.auth_token_encrypted, u.bio_token_encrypted, u.pid_data_encrypted, u.otp_encrypted, u.verify_otp_encrypted
        FROM uc_machine_map m
        INNER JOIN uc_operators u ON u.id = m.uc_operator_id
        WHERE m.macid = ?
        LIMIT 1");
    if(!$stmt){
        ucApiResponse(500, ['status' => false, 'message' => 'Database query ready nahi ho saki']);
    }
    mysqli_stmt_bind_param($stmt, 's', $macId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if(!$row){
        ucApiResponse(404, ['status' => false, 'message' => 'MAC ID ka UC mapping nahi mila']);
    }
    return $row;
}

function ucApiUploadCount($link, $macId, $secure){
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Allow: POST, OPTIONS');
        ucApiResponse(405, ['status' => false, 'message' => 'POST required hai']);
    }

    if($secure){
        if(isset($_GET['sid'])){
            ucApiResponse(422, ['status' => false, 'message' => 'sid URL mein nahi, JSON body mein bhejein']);
        }
        $body = file_get_contents('php://input', false, null, 0, 4097);
        if($body === false || strlen($body) > 4096){
            ucApiResponse(413, ['status' => false, 'message' => 'Request too large']);
        }
        $data = json_decode($body, true);
        if(!is_array($data)){
            ucApiResponse(422, ['status' => false, 'message' => 'JSON body required hai']);
        }
        $sid = $data['sid'] ?? null;
        $eventId = $data['eventId'] ?? null;
        if(!is_string($eventId) || !preg_match('/^[A-Za-z0-9_-]{8,128}$/D', $eventId)){
            ucApiResponse(422, ['status' => false, 'message' => 'Valid eventId required hai']);
        }
    } else {
        $sid = $_GET['sid'] ?? $_POST['sid'] ?? null;
    }
    if(!is_string($sid) || trim($sid) === '' || strlen($sid) > 255){
        ucApiResponse(422, ['status' => false, 'message' => 'sid required hai (max 255 characters)']);
    }
    $sid = trim($sid);

    if($secure){
        mysqli_begin_transaction($link);
        try {
            $stmt = mysqli_prepare($link, "INSERT INTO uc_upload_events (macid, event_id, sid) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE event_id = event_id");
            mysqli_stmt_bind_param($stmt, 'sss', $macId, $eventId, $sid);
            mysqli_stmt_execute($stmt);
            $isNew = mysqli_stmt_affected_rows($stmt) === 1;
            mysqli_stmt_close($stmt);

            if(!$isNew){
                $stmt = mysqli_prepare($link, "SELECT sid FROM uc_upload_events WHERE macid = ? AND event_id = ?");
                mysqli_stmt_bind_param($stmt, 'ss', $macId, $eventId);
                mysqli_stmt_execute($stmt);
                $previous = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);
                if(!$previous || !hash_equals($previous['sid'], $sid)){
                    mysqli_rollback($link);
                    ucApiResponse(409, ['status' => false, 'message' => 'eventId pehle alag sid ke liye use hua hai']);
                }
            }

            if($isNew){
                $stmt = mysqli_prepare($link, "INSERT INTO uc_upload_counts (macid, sid, upload_count)
                    VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE upload_count = upload_count + 1");
                mysqli_stmt_bind_param($stmt, 'ss', $macId, $sid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            mysqli_commit($link);
        } catch(Throwable $e){
            mysqli_rollback($link);
            throw $e;
        }
    } else {
        $stmt = mysqli_prepare($link, "INSERT INTO uc_upload_counts (macid, sid, upload_count)
            VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE upload_count = upload_count + 1");
        mysqli_stmt_bind_param($stmt, 'ss', $macId, $sid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $isNew = true;
    }

    $stmt = mysqli_prepare($link, "SELECT upload_count FROM uc_upload_counts WHERE macid = ? AND sid = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $macId, $sid);
    mysqli_stmt_execute($stmt);
    $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $response = ['status' => true, 'macId' => $macId, 'count' => (int)$count['upload_count'], 'counted' => $isNew];
    if(!$secure){
        $response['sid'] = $sid;
    }
    ucApiResponse(200, $response);
}

try {
    $route = ucApiRoute();

    $rawMacId = $_GET['macId'] ?? $_POST['macId'] ?? '';
    if(!is_string($rawMacId)){
        ucApiResponse(422, ['status' => false, 'message' => 'macId invalid hai']);
    }
    $macId = ucNormalizeMac($rawMacId);
    if($macId === '' || strlen($macId) > 100){
        ucApiResponse(422, ['status' => false, 'message' => 'macId required hai (max 100 characters)']);
    }
    // Status is intentionally public and contains only ACTIVE/INACTIVE.
    $secure = false;
    if($route !== 'getStatusUc'){
        $secure = ucApiAuthorize($link, $macId);
    }

    if($route === 'upload-uc-count'){
        ucApiUploadCount($link, $macId, $secure);
    }

    $operator = ucApiMappedOperator($link, $macId);

    if($route === 'getStatusUc'){
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo $operator['status'];
        exit();
    }

    if($operator['status'] !== 'ACTIVE'){
        ucApiResponse(403, ['status' => false, 'message' => 'UC mapping inactive hai']);
    }

    $fieldMap = [
        'get-auth-token' => 'auth_token_encrypted',
        'get-bio-token' => 'bio_token_encrypted',
        'get-pid' => 'pid_data_encrypted'
    ];

    if(isset($fieldMap[$route])){
        $value = ucDecrypt($operator[$fieldMap[$route]]);
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo $value;
        exit();
    }

    if($route === 'verify-otp'){
        if($operator['verify_otp_encrypted'] === null){
            ucApiResponse(404, ['status' => false, 'message' => 'verify-otp token operator ke liye set nahi hai']);
        }
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo ucDecrypt($operator['verify_otp_encrypted']);
        exit();
    }

    if($route === 'verify-otp-uc'){
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo ucDecrypt($operator['otp_encrypted']);
        exit();
    }

    ucApiResponse(404, ['status' => false, 'message' => 'API endpoint nahi mila']);
} catch(Throwable $e){
    ucApiResponse(500, ['status' => false, 'message' => 'UC data process nahi ho saka']);
}
