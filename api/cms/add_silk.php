<?php
/**
 * API: Cộng silk cho user (Admin only)
 * 
 * Workflow:
 * 1. Check admin authentication
 * 2. Validate input (JID, amount)
 * 3. Update SK_Silk table
 * 4. Log action
 * 5. Return result
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

// Check admin authentication
require_once __DIR__ . '/../../includes/auth_helper.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../connection_manager.php';
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $jid = intval($input['jid'] ?? $_POST['jid'] ?? 0);
    $amount = intval($input['amount'] ?? $_POST['amount'] ?? 0);
    
    // Validation
    if ($jid <= 0) {
        throw new Exception('JID không hợp lệ');
    }
    
    if ($amount <= 0) {
        throw new Exception('Số lượng silk phải lớn hơn 0');
    }
    
    if ($amount > 10000000) {
        throw new Exception('Số lượng silk quá lớn (tối đa 10,000,000)');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    $logDb = ConnectionManager::getLogDB();
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Check if user exists
    $checkUser = $accountDb->prepare("SELECT JID, StrUserID FROM TB_User WHERE JID = ?");
    $checkUser->execute([$jid]);
    $user = $checkUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User không tồn tại');
    }
    
    // Check if SK_Silk record exists
    $checkSilk = $accountDb->prepare("SELECT JID, silk_own FROM SK_Silk WHERE JID = ?");
    $checkSilk->execute([$jid]);
    $silkRecord = $checkSilk->fetch(PDO::FETCH_ASSOC);
    
    if (!$silkRecord) {
        // Create SK_Silk record if not exists
        $createSilk = $accountDb->prepare("INSERT INTO SK_Silk (JID, silk_own, silk_gift, silk_point) VALUES (?, 0, 0, 0)");
        $createSilk->execute([$jid]);
        $currentSilk = 0;
    } else {
        $currentSilk = intval($silkRecord['silk_own'] ?? 0);
    }
    
    // Update silk
    $newSilk = $currentSilk + $amount;
    $updateSilk = $accountDb->prepare("UPDATE SK_Silk SET silk_own = ? WHERE JID = ?");
    $updateSilk->execute([$newSilk, $jid]);
    
    // Log action
    try {
        $logStmt = $logDb->prepare("
            INSERT INTO _LogEventUser (UserJID, EventID, EventData, RegDate)
            VALUES (?, 99, ?, GETDATE())
        ");
        $logMessage = sprintf(
            "Admin [JID:%d] added %s silk to user [JID:%d, Username:%s]. Old: %s, New: %s",
            $adminJID,
            number_format($amount),
            $jid,
            $user['StrUserID'],
            number_format($currentSilk),
            number_format($newSilk)
        );
        $logStmt->execute([$jid, $logMessage]);
    } catch (Exception $e) {
        // Log error but don't fail the operation
        error_log("Failed to log silk addition: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cộng silk thành công',
        'data' => [
            'jid' => $jid,
            'username' => $user['StrUserID'],
            'amount_added' => $amount,
            'old_silk' => $currentSilk,
            'new_silk' => $newSilk
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
