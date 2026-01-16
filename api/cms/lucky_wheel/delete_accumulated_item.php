<?php
/**
 * API: Xóa vật phẩm mốc quay (Admin only)
 * Soft delete - set IsActive = 0
 * Item will not be displayed in admin or user interface
 * Logs are preserved in LuckyWheelAccumulatedLog, so users can still claim from their own logs
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
    $itemId = intval($input['id'] ?? $_POST['id'] ?? 0);
    
    if ($itemId <= 0) {
        throw new Exception('ID vật phẩm không hợp lệ');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    
    // Check if item exists
    $checkStmt = $accountDb->prepare("SELECT Id FROM LuckyWheelAccumulatedItems WHERE Id = ?");
    $checkStmt->execute([$itemId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Vật phẩm không tồn tại');
    }
    
    // Check if item is used in any claims
    $checkUsedStmt = $accountDb->prepare("SELECT COUNT(*) as cnt FROM LuckyWheelAccumulatedLog WHERE AccumulatedItemId = ?");
    $checkUsedStmt->execute([$itemId]);
    $usedCount = $checkUsedStmt->fetch()['cnt'];
    
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Soft delete - set IsActive = 0
    // Item will not be displayed in admin or user interface (filtered by IsActive = 1)
    // Logs are preserved in LuckyWheelAccumulatedLog, so users can still claim from their own logs
    $updateStmt = $accountDb->prepare("
        UPDATE LuckyWheelAccumulatedItems 
        SET IsActive = 0, 
            UpdatedDate = GETDATE(),
            UpdatedBy = ?
        WHERE Id = ?
    ");
    $updateStmt->execute([$adminJID, $itemId]);
    
    $message = 'Đã xóa vật phẩm mốc quay';
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'soft_delete' => true,
        'claims_count' => $usedCount
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
