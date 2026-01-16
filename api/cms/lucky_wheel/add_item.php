<?php
/**
 * API: Thêm vật phẩm vào vòng quay (Admin only)
 * 
 * Workflow:
 * 1. Validate input
 * 2. Check if item code already exists
 * 3. Insert new item
 * 4. Return result
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../../includes/auth_helper.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../../connection_manager.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    $itemName = trim($input['item_name'] ?? $_POST['item_name'] ?? '');
    $itemCode = trim($input['item_code'] ?? $_POST['item_code'] ?? '');
    $quantity = intval($input['quantity'] ?? $_POST['quantity'] ?? 1);
    $isRare = isset($input['is_rare']) ? (bool)$input['is_rare'] : (isset($_POST['is_rare']) ? $_POST['is_rare'] === '1' : false);
    $winRate = floatval($input['win_rate'] ?? $_POST['win_rate'] ?? 0);
    $displayOrder = intval($input['display_order'] ?? $_POST['display_order'] ?? 0);
    
    // Validation
    if (empty($itemName)) {
        throw new Exception('Tên vật phẩm không được để trống');
    }
    
    if (strlen($itemName) > 100) {
        throw new Exception('Tên vật phẩm không được quá 100 ký tự');
    }
    
    if (empty($itemCode)) {
        throw new Exception('Mã vật phẩm không được để trống');
    }
    
    if (strlen($itemCode) > 50) {
        throw new Exception('Mã vật phẩm không được quá 50 ký tự');
    }
    
    if ($quantity <= 0) {
        throw new Exception('Số lượng vật phẩm phải lớn hơn 0');
    }
    
    if ($winRate <= 0 || $winRate > 100) {
        throw new Exception('Tỉ lệ quay ra phải từ 0.01 đến 100');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Check if item code already exists
    $checkStmt = $accountDb->prepare("SELECT COUNT(*) as cnt FROM LuckyWheelItems WHERE ItemCode = ?");
    $checkStmt->execute([$itemCode]);
    if ($checkStmt->fetch()['cnt'] > 0) {
        throw new Exception('Mã vật phẩm đã tồn tại');
    }
    
    // Insert new item
    $insertStmt = $accountDb->prepare("
        INSERT INTO LuckyWheelItems 
        (ItemName, ItemCode, Quantity, IsRare, WinRate, DisplayOrder, IsActive, CreatedDate, UpdatedDate, CreatedBy, UpdatedBy)
        VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE(), GETDATE(), ?, ?)
    ");
    $insertStmt->execute([
        $itemName,
        $itemCode,
        $quantity,
        $isRare ? 1 : 0,
        $winRate,
        $displayOrder,
        $adminJID,
        $adminJID
    ]);
    
    $itemId = $accountDb->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm vật phẩm thành công',
        'data' => [
            'id' => intval($itemId),
            'item_name' => $itemName,
            'item_code' => $itemCode,
            'quantity' => $quantity,
            'is_rare' => $isRare,
            'win_rate' => $winRate
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
