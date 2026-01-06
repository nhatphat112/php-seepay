<?php
/**
 * API: Lấy tổng tiền đã nạp của user
 * GET /api/tichnap/get_total_money.php?userJID={int}
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": 500000,
 *   "message": "Success"
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once __DIR__ . '/../../connection_manager.php';
require_once __DIR__ . '/../../includes/tichnap_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login first.',
        'data' => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $userJID = $_SESSION['user_id'];
    
    // Nếu có userJID trong query param, kiểm tra phải là user hiện tại
    if (isset($_GET['userJID'])) {
        $requestedJID = (int)$_GET['userJID'];
        if ($requestedJID != $userJID) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Forbidden. You can only view your own total money.',
                'data' => 0
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    $db = ConnectionManager::getAccountDB();
    
    // Kiểm tra tính năng có đang hoạt động không
    $featureStatus = checkTichNapFeatureStatus($db);
    
    // Tính tổng tiền từ TB_Order (chỉ tính các đơn hàng đã hoàn thành)
    $totalMoney = getTotalMoneyFromOrders($userJID, $db);
    
    // Lấy thời gian sự kiện từ config
    $eventStartDate = null;
    $eventEndDate = null;
    if ($featureStatus['config']) {
        $eventStartDate = $featureStatus['config']['eventStartDate'] ?? null;
        $eventEndDate = $featureStatus['config']['eventEndDate'] ?? null;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $totalMoney,
        'message' => 'Success',
        'featureEnabled' => $featureStatus['enabled'],
        'inTimeRange' => $featureStatus['inTimeRange'],
        'featureMessage' => $featureStatus['message'],
        'eventStartDate' => $eventStartDate,
        'eventEndDate' => $eventEndDate
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'data' => 0
    ], JSON_UNESCAPED_UNICODE);
}

