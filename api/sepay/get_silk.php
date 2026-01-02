<?php
/**
 * Get Current Silk Amount API
 * GET /api/sepay/get_silk.php
 * 
 * Response:
 * {
 *   "success": true,
 *   "silk": 100000
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once __DIR__ . '/../../connection_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo '{"success":false,"error":"Internal server error"}';
    }
    exit;
}

try {
    $userJID = $_SESSION['user_id'];
    $db = ConnectionManager::getAccountDB();
    
    $stmt = $db->prepare("SELECT silk_own FROM SK_Silk WHERE JID = ?");
    $stmt->execute([$userJID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $silk = $result ? intval($result['silk_own']) : 0;
    
    http_response_code(200);
    try {
        echo json_encode([
            'success' => true,
            'silk' => $silk
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo '{"success":false,"error":"Internal server error","silk":0}';
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'silk' => 0
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        echo '{"success":false,"error":"Internal server error","silk":0}';
    }
}

