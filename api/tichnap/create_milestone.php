<?php
/**
 * API: Tạo mốc nạp mới (Admin only)
 * POST /api/tichnap/create_milestone.php
 * 
 * Request:
 * {
 *   "rank": 100000,
 *   "description": "Phần thưởng mốc 100k",
 *   "itemIds": ["guid1", "guid2", "guid3"]
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
    
    $rank = isset($input['rank']) ? (int)$input['rank'] : 0;
    $description = isset($input['description']) ? trim($input['description']) : '';
    $itemIds = isset($input['itemIds']) ? $input['itemIds'] : [];
    
    // Validate input
    if ($rank <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Rank phải lớn hơn 0'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($itemIds) || !is_array($itemIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Phải chọn ít nhất một vật phẩm'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validate GUIDs
    foreach ($itemIds as $itemId) {
        if (!isValidGuid($itemId)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid item ID format: ' . $itemId
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    $db = ConnectionManager::getAccountDB();
    
    // Check if rank already exists
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM SilkTichNap
        WHERE Rank = ? AND IsDelete = 0
    ");
    $stmt->execute([$rank]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Mốc nạp ' . formatVND($rank) . ' đã tồn tại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Disable tất cả mốc khác (chỉ cho phép 1 mốc active)
    $stmt = $db->prepare("
        UPDATE SilkTichNap
        SET IsActive = 0, UpdatedDate = GETDATE(), UpdatedId = ?
        WHERE IsDelete = 0 AND IsActive = 1
    ");
    $stmt->execute([$adminJID]);
    
    // Validate all items exist
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM GiftCodeItem
        WHERE Id IN ($placeholders) AND IsDelete = 0
    ");
    $stmt->execute($itemIds);
    $itemsExist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($itemsExist['count'] != count($itemIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Một hoặc nhiều vật phẩm không tồn tại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Create milestone (tự động set IsActive = 1 vì đã disable các mốc khác)
    $dsItem = implode(',', $itemIds);
    $stmt = $db->prepare("
        INSERT INTO SilkTichNap (Id, Rank, DsItem, Description, IsActive, CreatedDate, CreatedId)
        VALUES (NEWID(), ?, ?, ?, 1, GETDATE(), ?)
    ");
    
    $adminJID = $_SESSION['user_id'] ?? null;
    $stmt->execute([$rank, $dsItem, $description, $adminJID]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Đã tạo mốc nạp thành công',
        'data' => [
            'rank' => $rank,
            'price' => formatVND($rank),
            'description' => $description,
            'itemCount' => count($itemIds)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Create milestone error: " . $e->getMessage());
}

