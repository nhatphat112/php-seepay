<?php
/**
 * API: Reset tích lũy của user
 * POST /api/tichnap/reset_total_money.php
 * 
 * Body:
 * {
 *   "target": "all" | "user",
 *   "username": "username" (nếu target = "user")
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Đã reset tích lũy thành công",
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['target'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required field: target'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $target = $input['target'];
    $username = $input['username'] ?? null;
    
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
    
    // Reset total money
    $result = resetTotalMoney($userJID, $db);
    
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

