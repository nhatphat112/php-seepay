<?php
/**
 * Sepay Get User Orders API
 * GET /api/sepay/get_user_orders.php?limit=10
 * 
 * Response:
 * {
 *   "success": true,
 *   "orders": [...]
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once __DIR__ . '/../../includes/sepay_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized. Please login first.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo '{"success":false,"error":"Internal server error"}';
    }
    exit;
}

try {
    $userJID = $_SESSION['user_id'];
    $limit = intval($_GET['limit'] ?? 10);
    
    if ($limit < 1 || $limit > 100) {
        $limit = 10;
    }
    
    // Get user orders via SepayService
    $result = SepayService::getUserOrders($userJID, $limit);
    
    http_response_code(200);
    try {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo '{"success":false,"error":"Internal server error","orders":[]}';
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'orders' => []
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        echo '{"success":false,"error":"Internal server error","orders":[]}';
    }
}

