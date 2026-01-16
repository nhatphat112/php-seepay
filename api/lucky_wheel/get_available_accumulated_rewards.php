<?php
/**
 * API: Lấy danh sách phần thưởng tích lũy có thể nhận (User)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login first.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    
    $userJID = (int)$_SESSION['user_id'];
    
    // Get available rewards
    $rewards = getAvailableAccumulatedRewards($userJID);
    
    // Get total spins for display
    $totalSpins = getUserTotalSpins($userJID);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'rewards' => $rewards,
            'total_spins' => $totalSpins
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
