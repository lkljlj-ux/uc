<?php
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../includes/uc_security.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

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

function ucApiAuthorize(){
    $requireAuthValue = getenv('UC_API_REQUIRE_AUTH');
    $requireAuth = strtolower((string)($requireAuthValue === false ? '1' : $requireAuthValue));
    if(in_array($requireAuth, ['0', 'false', 'no', 'off'], true)){
        return;
    }

    $expected = getenv('UC_API_KEY');
    if($expected === false || $expected === ''){
        // Backward compatibility for existing installations.
        $expected = getenv('SESSION_SECRET');
    }
    $received = ucApiHeader('X-Auth-Token');
    if($expected === false || $expected === '' || $received === '' || !hash_equals($expected, $received)){
        ucApiResponse(401, ['status' => false, 'message' => 'Unauthorized']);
    }
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

try {
    $route = ucApiRoute();

    // Public status checks are used by mapped devices before they can attach
    // the private API token. All data-bearing UC endpoints stay protected.
    if($route !== 'getStatusUc'){
        ucApiAuthorize();
    }

    $macId = ucNormalizeMac($_GET['macId'] ?? $_POST['macId'] ?? '');
    if($macId === ''){
        ucApiResponse(422, ['status' => false, 'message' => 'macId required hai']);
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

    if($route === 'upload-uc-count'){
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Allow: POST, OPTIONS');
            ucApiResponse(405, ['status' => false, 'message' => 'POST required hai']);
        }

        $sid = $_GET['sid'] ?? $_POST['sid'] ?? '';
        if(!is_string($sid) || trim($sid) === '' || strlen($sid) > 255){
            ucApiResponse(422, ['status' => false, 'message' => 'sid required hai (max 255 characters)']);
        }
        $sid = trim($sid);

        $stmt = mysqli_prepare($link, "INSERT INTO uc_upload_counts (macid, sid, upload_count)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE upload_count = upload_count + 1");
        if(!$stmt){
            throw new RuntimeException('UC upload count query unavailable');
        }
        mysqli_stmt_bind_param($stmt, 'ss', $macId, $sid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($link, "SELECT upload_count FROM uc_upload_counts WHERE macid = ? AND sid = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $macId, $sid);
        mysqli_stmt_execute($stmt);
        $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        ucApiResponse(200, ['status' => true, 'macId' => $macId, 'sid' => $sid, 'count' => (int)$count['upload_count']]);
    }

    $fieldMap = [
        'get-auth-token' => ['authToken', 'auth_token_encrypted'],
        'get-bio-token' => ['bioToken', 'bio_token_encrypted'],
        'get-pid' => ['pidData', 'pid_data_encrypted']
    ];

    if(isset($fieldMap[$route])){
        [$responseKey, $databaseKey] = $fieldMap[$route];
        $value = ucDecrypt($operator[$databaseKey]);

        if(in_array($route, ['get-auth-token', 'get-bio-token'], true)){
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(200);
            echo $value;
            exit();
        }

        ucApiResponse(200, [
            'status' => true,
            'macId' => $operator['macid'],
            $responseKey => $value
        ]);
    }

    if($route === 'verify-otp'){
        if($operator['verify_otp_encrypted'] === null){
            ucApiResponse(404, ['status' => false, 'message' => 'verify-otp token operator ke liye set nahi hai']);
        }
        ucApiResponse(200, [
            'status' => true,
            'macId' => $operator['macid'],
            'otp' => ucDecrypt($operator['verify_otp_encrypted'])
        ]);
    }

    if($route === 'verify-otp-uc'){
        ucApiResponse(200, [
            'status' => true,
            'macId' => $operator['macid'],
            'otp' => ucDecrypt($operator['otp_encrypted'])
        ]);
    }

    ucApiResponse(404, ['status' => false, 'message' => 'API endpoint nahi mila']);
} catch(Throwable $e){
    ucApiResponse(500, ['status' => false, 'message' => 'UC data process nahi ho saka']);
}
