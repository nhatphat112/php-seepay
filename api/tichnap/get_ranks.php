<?php
/**
 * API: Lấy danh sách mốc nạp
 * GET /api/tichnap/get_ranks.php
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "id": "guid-1",
 *       "price": "100.000 VND",
 *       "priceValue": 100000,
 *       "items": [...]
 *     }
 *   ]
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../connection_manager.php';
require_once __DIR__ . '/../../includes/tichnap_helper.php';

try {
    $db = ConnectionManager::getAccountDB();
    
    // 1. Lấy mốc nạp active (chưa bị xóa, chỉ lấy mốc đang active)
    $stmt = $db->prepare("
        SELECT Id, Rank, DsItem, Description
        FROM SilkTichNap
        WHERE IsDelete = 0 AND IsActive = 1
        ORDER BY Rank ASC
    ");
    $stmt->execute();
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    // 2. Với mỗi mốc, lấy thông tin items
    foreach ($milestones as $milestone) {
        $itemIds = parseItemIds($milestone['DsItem']);
        
        $items = [];
        
        if (!empty($itemIds)) {
            // Tạo placeholders cho IN clause
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            
            // Lấy thông tin items
            $stmt = $db->prepare("
                SELECT Id, CodeItem, NameItem, quanlity
                FROM GiftCodeItem
                WHERE Id IN ($placeholders) AND IsDelete = 0
            ");
            $stmt->execute($itemIds);
            $giftItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Với mỗi item, lấy hình ảnh
            foreach ($giftItems as $giftItem) {
                // Lấy hình ảnh (IconVP)
                $stmt = $db->prepare("
                    SELECT DuongDanFile
                    FROM TaiLieuDinhKem
                    WHERE Item_ID = ? AND LoaiTaiLieu = 'IconVP'
                    ORDER BY CreatedDate DESC
                ");
                $stmt->execute([$giftItem['Id']]);
                $image = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $itemName = $giftItem['NameItem'] ?: $giftItem['CodeItem'];
                $quantity = (int)$giftItem['quanlity'];
                
                $items[] = [
                    'id' => $giftItem['Id'],  // Thêm ID để admin có thể reference
                    'key' => $giftItem['CodeItem'],
                    'name' => $itemName,  // Tên item (luôn có)
                    'quantity' => $quantity,  // Số lượng riêng
                    'displayName' => $quantity > 1 ? "$itemName x ($quantity)" : $itemName,  // Tên hiển thị với số lượng
                    'image' => $image['DuongDanFile'] ?? null  // Hình ảnh (nếu có)
                ];
            }
        }
        
        $result[] = [
            'id' => $milestone['Id'],
            'price' => formatVND($milestone['Rank']),
            'priceValue' => (int)$milestone['Rank'],
            'description' => $milestone['Description'] ?? '',
            'items' => $items
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}

