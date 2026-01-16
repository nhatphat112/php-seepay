<?php
/**
 * API: Cập nhật vật phẩm vòng quay (Admin only)
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
    $itemId = intval($input['id'] ?? $_POST['id'] ?? 0);
    $itemName = trim($input['item_name'] ?? $_POST['item_name'] ?? '');
    $itemCode = trim($input['item_code'] ?? $_POST['item_code'] ?? '');
    $quantity = intval($input['quantity'] ?? $_POST['quantity'] ?? 1);
    $isRare = isset($input['is_rare']) ? (bool)$input['is_rare'] : (isset($_POST['is_rare']) ? $_POST['is_rare'] === '1' : false);
    $winRate = floatval($input['win_rate'] ?? $_POST['win_rate'] ?? 0);
    $displayOrder = intval($input['display_order'] ?? $_POST['display_order'] ?? 0);
    $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : (isset($_POST['is_active']) ? $_POST['is_active'] === '1' : true);
    
    // Validation
    if ($itemId <= 0) {
        throw new Exception('ID vật phẩm không hợp lệ');
    }
    
    if (empty($itemName)) {
        throw new Exception('Tên vật phẩm không được để trống');
    }
    
    if (strlen($itemName) > 100) {
        throw new Exception('Tên vật phẩm không được quá 100 ký tự');
    }
    
    if (empty($itemCode)) {
        throw new Exception('Mã vật phẩm không được để trống');
    }
    
    if ($quantity <= 0) {
        throw new Exception('Số lượng vật phẩm phải lớn hơn 0');
    }
    
    if ($winRate <= 0 || $winRate > 100) {
        throw new Exception('Tỉ lệ quay ra phải từ 0.01 đến 100');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Check if item exists
    $checkStmt = $accountDb->prepare("SELECT Id FROM LuckyWheelItems WHERE Id = ?");
    $checkStmt->execute([$itemId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Vật phẩm không tồn tại');
    }
    
    // Check if item code already exists for another item
    $checkCodeStmt = $accountDb->prepare("SELECT Id FROM LuckyWheelItems WHERE ItemCode = ? AND Id != ?");
    $checkCodeStmt->execute([$itemCode, $itemId]);
    if ($checkCodeStmt->fetch()) {
        throw new Exception('Mã vật phẩm đã được sử dụng bởi vật phẩm khác');
    }
    
    // Update item
    $updateStmt = $accountDb->prepare("
        UPDATE LuckyWheelItems
        SET ItemName = ?,
            ItemCode = ?,
            Quantity = ?,
            IsRare = ?,
            WinRate = ?,
            DisplayOrder = ?,
            IsActive = ?,
            UpdatedDate = GETDATE(),
            UpdatedBy = ?
        WHERE Id = ?
    ");
    $updateStmt->execute([
        $itemName,
        $itemCode,
        $quantity,
        $isRare ? 1 : 0,
        $winRate,
        $displayOrder,
        $isActive ? 1 : 0,
        $adminJID,
        $itemId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật vật phẩm thành công',
        'data' => [
            'id' => $itemId,
            'item_name' => $itemName,
            'item_code' => $itemCode,
            'quantity' => $quantity,
            'is_rare' => $isRare,
            'win_rate' => $winRate,
            'is_active' => $isActive
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
