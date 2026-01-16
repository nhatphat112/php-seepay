<?php
/**
 * API: Xóa vật phẩm vòng quay (Admin only)
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
    
    if ($itemId <= 0) {
        throw new Exception('ID vật phẩm không hợp lệ');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    
    // Check if item exists
    $checkStmt = $accountDb->prepare("SELECT Id FROM LuckyWheelItems WHERE Id = ?");
    $checkStmt->execute([$itemId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Vật phẩm không tồn tại');
    }
    
    // Check if item is used in any rewards (soft delete by setting IsActive = 0)
    $checkUsedStmt = $accountDb->prepare("SELECT COUNT(*) as cnt FROM LuckyWheelRewards WHERE ItemId = ? AND Status = 'pending'");
    $checkUsedStmt->execute([$itemId]);
    $usedCount = $checkUsedStmt->fetch()['cnt'];
    
    if ($usedCount > 0) {
        // Soft delete - set IsActive = 0
        $updateStmt = $accountDb->prepare("UPDATE LuckyWheelItems SET IsActive = 0 WHERE Id = ?");
        $updateStmt->execute([$itemId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã vô hiệu hóa vật phẩm (có ' . $usedCount . ' phần thưởng đang chờ nhận)',
            'soft_delete' => true
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Hard delete - remove item
        $deleteStmt = $accountDb->prepare("DELETE FROM LuckyWheelItems WHERE Id = ?");
        $deleteStmt->execute([$itemId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa vật phẩm thành công',
            'soft_delete' => false
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
