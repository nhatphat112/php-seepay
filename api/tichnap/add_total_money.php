<?php
/**
 * API: Cộng tích lũy cho user
 * POST /api/tichnap/add_total_money.php
 * 
 * Body:
 * {
 *   "target": "all" | "user",
 *   "username": "username" (nếu target = "user"),
 *   "amount": 100000
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Đã cộng tích lũy thành công",
 *   "affected": 10
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once __DIR__ . '/../../connection_manager.php';
require_once __DIR__ . '/../../includes/tichnap_helper.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // Log for debugging
    error_log("add_total_money.php - Raw input: " . $rawInput);
    error_log("add_total_money.php - Parsed input: " . print_r($input, true));
    
    if (!isset($input['target']) || !isset($input['amount'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: target, amount',
            'debug' => ['received' => $input]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $target = trim($input['target']);
    $username = isset($input['username']) && !empty($input['username']) ? trim($input['username']) : null;
    $amount = isset($input['amount']) ? (int)$input['amount'] : 0;
    
    if ($target !== 'all' && $target !== 'user') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid target. Must be "all" or "user"'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($target === 'user' && empty($username)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Username is required when target is "user"'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Amount must be greater than 0'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = ConnectionManager::getAccountDB();
    
    $userJID = null;
    if ($target === 'user') {
        // Get JID from username
        $userJID = getJIDFromUsername($username, $db);
        if ($userJID === null) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => "User không tồn tại: {$username}"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    // Log before calling addTotalMoney
    error_log("add_total_money.php - Calling addTotalMoney with userJID: " . ($userJID ?? 'null') . ", amount: {$amount}, target: {$target}");
    
    // Add total money
    $result = addTotalMoney($userJID, $amount, $db);
    
    // Log result
    error_log("add_total_money.php - Result: " . print_r($result, true));
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'affected' => $result['affected']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['message']
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


