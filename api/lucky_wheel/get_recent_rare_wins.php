<?php
/**
 * API: Lấy danh sách user nhận vật phẩm hiếm (Public)
 * Dùng cho ticker trên trang chủ
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    
    $limit = intval($_GET['limit'] ?? 20);
    $limit = max(1, min(100, $limit)); // Limit between 1 and 100
    
    $wins = getRecentRareWins($limit);
    
    // Format results
    $results = [];
    foreach ($wins as $win) {
        // Provide ISO-8601 timestamp for reliable client-side parsing (timezone-aware)
        $wonDateIso = null;
        try {
            $wonDateIso = (new DateTime($win['WonDate']))->format(DATE_ATOM);
        } catch (Exception $e) {
            $wonDateIso = null;
        }
        $results[] = [
            'username' => $win['Username'],
            'item_name' => $win['ItemName'],
            'won_date' => $win['WonDate'],
            'won_date_iso' => $wonDateIso
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results
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
