<?php
/**
 * API: Quay vòng quay may mắn (User)
 * 
 * Workflow:
 * 1. Check user authentication
 * 2. Validate spin count
 * 3. Check feature enabled
 * 4. Process spin (deduct silk, create log/reward)
 * 5. Return results
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../includes/auth_helper.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login.'
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
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    $spinCount = intval($input['spin_count'] ?? $_POST['spin_count'] ?? 1);
    
    // Validation
    if ($spinCount <= 0 || $spinCount > 20) {
        throw new Exception('Số lần quay phải từ 1 đến 20');
    }
    
    $userJID = $_SESSION['user_id'];
    
    // Process spin
    $result = processSpin($userJID, $spinCount);
    
    echo json_encode([
        'success' => true,
        'message' => 'Quay thành công',
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
