<?php
/**
 * Sepay Get Order Status API
 * GET /api/sepay/get_order_status.php?order_code=ORDER12345678901234
 * 
 * Response:
 * {
 *   "success": true,
 *   "order": {
 *     "OrderID": 1,
 *     "OrderCode": "ORDER12345678901234",
 *     "Status": "completed",
 *     ...
 *   }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../includes/sepay_service.php';

// Get order code from query string
$orderCode = $_GET['order_code'] ?? '';

if (empty($orderCode)) {
    http_response_code(400);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Missing order_code parameter'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo '{"success":false,"error":"Internal server error"}';
    }
    exit;
}

try {
    // Get order status via SepayService
    $result = SepayService::getOrderStatus($orderCode);
    
    if ($result['success']) {
        http_response_code(200);
        try {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo '{"success":false,"error":"Internal server error"}';
        }
    } else {
        http_response_code(404);
        try {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo '{"success":false,"error":"Internal server error"}';
        }
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        echo '{"success":false,"error":"Internal server error"}';
    }
}

