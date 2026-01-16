<?php
/**
 * API: Lấy danh sách vật phẩm trong vòng quay (Public/User)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    
    $items = getLuckyWheelItems(false); // Only active items
    
    // Format for display
    $results = [];
    foreach ($items as $item) {
        $results[] = [
            'id' => intval($item['Id']),
            'item_name' => $item['ItemName'],
            'item_code' => $item['ItemCode'],
            'quantity' => intval($item['Quantity']),
            'is_rare' => (bool)$item['IsRare'],
            'win_rate' => floatval($item['WinRate']),
            'display_order' => intval($item['DisplayOrder'])
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
