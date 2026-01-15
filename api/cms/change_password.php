<?php
/**
 * API: Đổi mật khẩu user bằng email (Admin only)
 * 
 * Workflow:
 * 1. Check admin authentication
 * 2. Validate input (email, new_password)
 * 3. Find user by email
 * 4. Update password (MD5 hash)
 * 5. Log action
 * 6. Return result
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
    $email = trim($input['email'] ?? $_POST['email'] ?? '');
    $newPassword = $input['new_password'] ?? $_POST['new_password'] ?? '';
    
    // Validation
    if (empty($email)) {
        throw new Exception('Email không được để trống');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không hợp lệ');
    }
    
    if (empty($newPassword)) {
        throw new Exception('Mật khẩu mới không được để trống');
    }
    
    if (strlen($newPassword) < 6) {
        throw new Exception('Mật khẩu phải có ít nhất 6 ký tự');
    }
    
    $accountDb = ConnectionManager::getAccountDB();
    $logDb = ConnectionManager::getLogDB();
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Find user by email
    $findUser = $accountDb->prepare("SELECT JID, StrUserID, Email FROM TB_User WHERE Email = ?");
    $findUser->execute([$email]);
    $user = $findUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Không tìm thấy user với email: ' . htmlspecialchars($email));
    }
    
    // Hash password (MD5 for Silkroad compatibility)
    $hashedPassword = md5($newPassword);
    
    // Update password
    $updatePassword = $accountDb->prepare("UPDATE TB_User SET password = ? WHERE JID = ?");
    $updatePassword->execute([$hashedPassword, $user['JID']]);
    
    // Log action
    try {
        $logStmt = $logDb->prepare("
            INSERT INTO _LogEventUser (UserJID, EventID, EventData, RegDate)
            VALUES (?, 98, ?, GETDATE())
        ");
        $logMessage = sprintf(
            "Admin [JID:%d] changed password for user [JID:%d, Username:%s, Email:%s]",
            $adminJID,
            $user['JID'],
            $user['StrUserID'],
            $email
        );
        $logStmt->execute([$user['JID'], $logMessage]);
    } catch (Exception $e) {
        // Log error but don't fail the operation
        error_log("Failed to log password change: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã đổi mật khẩu thành công',
        'data' => [
            'jid' => intval($user['JID']),
            'username' => $user['StrUserID'],
            'email' => $email
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
