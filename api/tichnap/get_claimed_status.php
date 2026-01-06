<?php
/**
 * API: Lấy danh sách mốc đã nhận
 * GET /api/tichnap/get_claimed_status.php?username={username}
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "idItem": "guid-1",
 *       "isActive": true,
 *       "maxPrice": 100000
 *     }
 *   ]
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once __DIR__ . '/../../connection_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login first.',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $username = $_SESSION['username'];
    
    // Nếu có username trong query param, kiểm tra phải là user hiện tại
    if (isset($_GET['username'])) {
        $requestedUsername = trim($_GET['username']);
        if ($requestedUsername != $username) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Forbidden. You can only view your own claimed status.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    $db = ConnectionManager::getAccountDB();
    
    // Lấy danh sách mốc đã nhận
    $stmt = $db->prepare("
        SELECT IdTichNap, MaxPrice
        FROM LogTichNap
        WHERE CharName = ? AND Status = 1 AND IsDelete = 0
    ");
    $stmt->execute([$username]);
    $claimed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = array_map(function($item) {
        return [
            'idItem' => $item['IdTichNap'],
            'isActive' => true,
            'maxPrice' => (int)$item['MaxPrice']
        ];
    }, $claimed);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}

