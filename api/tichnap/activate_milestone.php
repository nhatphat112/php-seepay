<?php
/**
 * API: Kích hoạt mốc nạp (Admin only)
 * POST /api/tichnap/activate_milestone.php
 * 
 * Request:
 * {
 *   "id": "guid-1"
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../connection_manager.php';
require_once __DIR__ . '/../../includes/tichnap_helper.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $id = isset($input['id']) ? trim($input['id']) : '';
    
    // Validate input
    if (empty($id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID is required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!isValidGuid($id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid ID format'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = ConnectionManager::getAccountDB();
    
    // Check if milestone exists
    $stmt = $db->prepare("
        SELECT Rank FROM SilkTichNap WHERE Id = ? AND IsDelete = 0
    ");
    $stmt->execute([$id]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$milestone) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Mốc nạp không tồn tại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Disable tất cả mốc khác (chỉ cho phép 1 mốc active)
    // UpdatedId là UNIQUEIDENTIFIER, không thể dùng INT từ session, set NULL
    $stmt = $db->prepare("
        UPDATE SilkTichNap
        SET IsActive = 0, UpdatedDate = GETDATE(), UpdatedId = NULL
        WHERE IsDelete = 0 AND IsActive = 1
    ");
    $stmt->execute();
    
    // Activate mốc được chọn
    // UpdatedId là UNIQUEIDENTIFIER, không thể dùng INT từ session, set NULL
    $stmt = $db->prepare("
        UPDATE SilkTichNap
        SET IsActive = 1, UpdatedDate = GETDATE(), UpdatedId = NULL
        WHERE Id = ?
    ");
    $stmt->execute([$id]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Đã kích hoạt mốc nạp thành công'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Activate milestone error: " . $e->getMessage());
}

