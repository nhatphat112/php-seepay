<?php
/**
 * API: Lấy cấu hình vòng quay (Admin only)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../../includes/auth_helper.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../../includes/lucky_wheel_helper.php';
    
    $config = getLuckyWheelConfig();
    
    echo json_encode([
        'success' => true,
        'data' => $config
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
