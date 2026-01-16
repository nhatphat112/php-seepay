<?php
/**
 * API: Bật/tắt tính năng vòng quay (Admin only)
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
    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : (isset($_POST['enabled']) ? $_POST['enabled'] === '1' : false);
    $spinCost = isset($input['spin_cost']) ? intval($input['spin_cost']) : (isset($_POST['spin_cost']) ? intval($_POST['spin_cost']) : 10);
    
    if ($spinCost <= 0) {
        throw new Exception('Giá quay phải lớn hơn 0');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Update or insert config
    $checkStmt = $accountDb->query("SELECT COUNT(*) as cnt FROM LuckyWheelConfig");
    $configExists = $checkStmt->fetch()['cnt'] > 0;
    
    if ($configExists) {
        $updateStmt = $accountDb->prepare("
            UPDATE LuckyWheelConfig
            SET FeatureEnabled = ?,
                SpinCost = ?,
                UpdatedDate = GETDATE(),
                UpdatedBy = ?
        ");
        $updateStmt->execute([$enabled ? 1 : 0, $spinCost, $adminJID]);
    } else {
        $insertStmt = $accountDb->prepare("
            INSERT INTO LuckyWheelConfig (FeatureEnabled, SpinCost, UpdatedDate, UpdatedBy)
            VALUES (?, ?, GETDATE(), ?)
        ");
        $insertStmt->execute([$enabled ? 1 : 0, $spinCost, $adminJID]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $enabled ? 'Đã bật tính năng vòng quay' : 'Đã tắt tính năng vòng quay',
        'data' => [
            'feature_enabled' => $enabled,
            'spin_cost' => $spinCost
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
