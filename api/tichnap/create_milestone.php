<?php
/**
 * API: Tạo mốc nạp mới (Admin only)
 * POST /api/tichnap/create_milestone.php
 * 
 * Request:
 * {
 *   "rank": 100000,
 *   "description": "Phần thưởng mốc 100k",
 *   "items": [
 *     {"name": "Quiver", "codeItem": "ITEM_MALL_QUIVER", "quantity": 1},
 *     {"name": "Potion", "codeItem": "ITEM_MALL_POTION", "quantity": 10}
 *   ]
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
    $items = isset($input['items']) ? $input['items'] : [];
    
    // Validate input
    if ($rank <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Rank phải lớn hơn 0'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($items) || !is_array($items)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Phải thêm ít nhất một vật phẩm'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validate items
    foreach ($items as $item) {
        if (empty($item['name']) || empty($item['codeItem'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Mỗi vật phẩm phải có tên và ID (CodeItem)'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
        if ($quantity <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Số lượng vật phẩm phải lớn hơn 0'
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
    
    // Chuẩn hóa items data
    $normalizedItems = [];
    foreach ($items as $item) {
        $normalizedItems[] = [
            'name' => trim($item['name']),
            'codeItem' => trim($item['codeItem']),
            'quantity' => (int)($item['quantity'] ?? 1)
        ];
    }
    
    // Lưu items dưới dạng JSON
    $itemsJson = json_encode($normalizedItems, JSON_UNESCAPED_UNICODE);
    
    // Giữ DsItem rỗng để tương thích với code cũ (nếu có)
    $dsItem = '';
    
    // Create milestone (mặc định IsActive = 1, tất cả mốc đều active)
    // CreatedId là UNIQUEIDENTIFIER, không thể dùng INT từ session, set NULL
    $stmt = $db->prepare("
        INSERT INTO SilkTichNap (Id, Rank, DsItem, ItemsJson, Description, IsActive, CreatedDate, CreatedId)
        VALUES (NEWID(), ?, ?, ?, ?, 1, GETDATE(), NULL)
    ");
    
    $stmt->execute([$rank, $dsItem, $itemsJson, $description]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Đã tạo mốc nạp thành công',
        'data' => [
            'rank' => $rank,
            'price' => formatVND($rank),
            'description' => $description,
            'itemCount' => count($normalizedItems),
            'items' => $normalizedItems
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

