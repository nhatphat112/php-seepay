<?php
/**
 * API: Cập nhật vật phẩm tích lũy (Admin only)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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
    $itemId = intval($input['id'] ?? 0);
    $itemName = trim($input['item_name'] ?? '');
    $itemCode = trim($input['item_code'] ?? '');
    $quantity = intval($input['quantity'] ?? 1);
    $requiredSpins = intval($input['required_spins'] ?? 0);
    
    if ($itemId <= 0) {
        throw new Exception('ID vật phẩm không hợp lệ');
    }
    
    // Validation
    if (empty($itemName)) {
        throw new Exception('Tên vật phẩm không được để trống');
    }
    
    if (strlen($itemName) > 100) {
        throw new Exception('Tên vật phẩm không được vượt quá 100 ký tự');
    }
    
    if (empty($itemCode)) {
        throw new Exception('Mã vật phẩm không được để trống');
    }
    
    if (strlen($itemCode) > 50) {
        throw new Exception('Mã vật phẩm không được vượt quá 50 ký tự');
    }
    
    if ($quantity <= 0) {
        throw new Exception('Số lượng phải lớn hơn 0');
    }
    
    if ($requiredSpins <= 0) {
        throw new Exception('Mức đạt (số vòng) phải lớn hơn 0');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Check if item exists
    $checkStmt = $accountDb->prepare("SELECT Id, DisplayOrder FROM LuckyWheelAccumulatedItems WHERE Id = ?");
    $checkStmt->execute([$itemId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        throw new Exception('Vật phẩm không tồn tại');
    }
    
    // Retain existing DisplayOrder
    $displayOrder = intval($existing['DisplayOrder']);
    
    $stmt = $accountDb->prepare("
        UPDATE LuckyWheelAccumulatedItems
        SET ItemName = ?,
            ItemCode = ?,
            Quantity = ?,
            RequiredSpins = ?,
            UpdatedDate = GETDATE(),
            UpdatedBy = ?
        WHERE Id = ?
    ");
    
    $stmt->execute([
        $itemName,
        $itemCode,
        $quantity,
        $requiredSpins,
        $adminJID,
        $itemId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật vật phẩm tích lũy thành công',
        'data' => [
            'id' => $itemId,
            'item_name' => $itemName,
            'item_code' => $itemCode,
            'quantity' => $quantity,
            'required_spins' => $requiredSpins,
            'display_order' => $displayOrder
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
