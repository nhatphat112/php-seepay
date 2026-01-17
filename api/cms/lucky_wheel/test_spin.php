<?php
/**
 * API: Test Spin - Mô phỏng quay vòng (không trừ silk, chỉ test)
 * 
 * Workflow:
 * 1. Validate input (số lượng vòng quay)
 * 2. Lấy danh sách vật phẩm active
 * 3. Mô phỏng quay nhiều lần
 * 4. Thống kê kết quả
 * 5. Return results
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../../admin/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../../includes/lucky_wheel_helper.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    $spinCount = intval($input['spin_count'] ?? $_POST['spin_count'] ?? 1);
    
    // Validation
    if ($spinCount <= 0 || $spinCount > 10000) {
        throw new Exception('Số lần quay phải từ 1 đến 10000');
    }
    
    // Get active items
    $items = getLuckyWheelItems(false);
    
    if (empty($items)) {
        throw new Exception("Không có vật phẩm nào trong vòng quay");
    }
    
    // Filter valid items with win rate > 0
    $validItems = [];
    $totalRate = 0;
    
    foreach ($items as $item) {
        $winRate = floatval($item['WinRate']);
        if ($winRate > 0) {
            $validItems[] = $item;
            $totalRate += $winRate;
        }
    }
    
    if (empty($validItems)) {
        throw new Exception("Không có vật phẩm hợp lệ với tỉ lệ quay > 0");
    }
    
    // Initialize statistics
    $stats = [];
    foreach ($validItems as $item) {
        $stats[$item['Id']] = [
            'item_id' => $item['Id'],
            'item_name' => $item['ItemName'],
            'item_code' => $item['ItemCode'],
            'quantity' => $item['Quantity'],
            'is_rare' => (bool)$item['IsRare'],
            'win_rate' => floatval($item['WinRate']),
            'expected_count' => round(($item['WinRate'] / $totalRate) * $spinCount, 2),
            'actual_count' => 0,
            'actual_percentage' => 0
        ];
    }
    
    // Simulate spins
    $maxValue = intval($totalRate * 10000);
    $results = [];
    
    for ($i = 0; $i < $spinCount; $i++) {
        $random = mt_rand(0, $maxValue) / 10000;
        
        $cumulative = 0;
        foreach ($validItems as $item) {
            $cumulative += floatval($item['WinRate']);
            if ($random <= $cumulative) {
                $itemId = $item['Id'];
                $stats[$itemId]['actual_count']++;
                $results[] = [
                    'spin_number' => $i + 1,
                    'item_id' => $item['Id'],
                    'item_name' => $item['ItemName'],
                    'item_code' => $item['ItemCode'],
                    'quantity' => $item['Quantity'],
                    'is_rare' => (bool)$item['IsRare']
                ];
                break;
            }
        }
    }
    
    // Calculate actual percentages
    foreach ($stats as $itemId => &$stat) {
        $stat['actual_percentage'] = $spinCount > 0 ? round(($stat['actual_count'] / $spinCount) * 100, 2) : 0;
    }
    
    // Convert stats to array for JSON
    $statsArray = array_values($stats);
    
    echo json_encode([
        'success' => true,
        'message' => "Đã mô phỏng $spinCount lần quay",
        'data' => [
            'spin_count' => $spinCount,
            'total_rate' => $totalRate,
            'items_count' => count($validItems),
            'statistics' => $statsArray,
            'results' => $results
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
