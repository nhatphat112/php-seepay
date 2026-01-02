<?php
/**
 * Sepay Create Order API
 * POST /api/sepay/create_order.php
 * 
 * Request:
 * {
 *   "amount": 100000,
 *   "payment_method": "QR_CODE"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "order_code": "ORDER12345678901234",
 *   "qr_code": "https://...",
 *   "bank_account": "1234567890",
 *   ...
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use POST.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo '{"success":false,"error":"Internal server error"}';
    }
    exit;
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback to POST data if JSON is empty
    if (empty($input)) {
        $input = $_POST;
    }
    
    $amount = floatval($input['amount'] ?? 0);
    $paymentMethod = $input['payment_method'] ?? 'QR_CODE';
    
    $userJID = $_SESSION['user_id'];
    
    // Validate input
    if ($amount < 10000 || $amount > 10000000) {
        http_response_code(400);
        try {
            echo json_encode([
                'success' => false,
                'error' => 'Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo '{"success":false,"error":"Internal server error"}';
        }
        exit;
    }
    
    if (!in_array($paymentMethod, ['QR_CODE', 'BANK_TRANSFER'])) {
        http_response_code(400);
        try {
            echo json_encode([
                'success' => false,
                'error' => 'Phương thức thanh toán không hợp lệ. Chỉ chấp nhận: QR_CODE, BANK_TRANSFER'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo '{"success":false,"error":"Internal server error"}';
        }
        exit;
    }
    
    // Get user info from session
    $username = $_SESSION['username'] ?? 'User';
    
    // Create order via SepayService
    $result = SepayService::createOrder(
        $userJID,
        $username,
        $amount,
        $paymentMethod
    );
    
    if ($result['success']) {
        http_response_code(200);
        try {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo '{"success":false,"error":"Internal server error"}';
        }
    } else {
        http_response_code(400);
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
            'error' => 'Internal server error. Please try again later.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        echo '{"success":false,"error":"Internal server error"}';
    }
}

