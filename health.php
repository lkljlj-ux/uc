<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/database.php';
    $result = mysqli_query($link, 'SELECT 1 AS healthy');
    if(!$result){
        throw new RuntimeException('Database check failed');
    }
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} catch(Throwable $e){
    error_log('Health check failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'unavailable']);
}