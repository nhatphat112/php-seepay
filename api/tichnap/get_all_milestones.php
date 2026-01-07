<?php
/**
 * API: Lấy tất cả mốc nạp (Admin only - bao gồm cả inactive)
 * GET /api/tichnap/get_all_milestones.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../connection_manager.php';
require_once __DIR__ . '/../../includes/tichnap_helper.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = ConnectionManager::getAccountDB();
    
    // Lấy tất cả mốc nạp (chưa bị xóa, bao gồm cả inactive)
    $stmt = $db->prepare("
        SELECT Id, Rank, DsItem, ItemsJson, Description, IsActive
        FROM SilkTichNap
        WHERE IsDelete = 0
        ORDER BY Rank ASC
    ");
    $stmt->execute();
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    // Với mỗi mốc, lấy thông tin items
    foreach ($milestones as $milestone) {
        $items = [];
        
        // Ưu tiên đọc từ ItemsJson (cách mới)
        if (!empty($milestone['ItemsJson'])) {
            $itemsData = json_decode($milestone['ItemsJson'], true);
            if (is_array($itemsData)) {
                foreach ($itemsData as $itemData) {
                    $itemName = $itemData['name'] ?? '';
                    $codeItem = $itemData['codeItem'] ?? '';
                    $quantity = (int)($itemData['quantity'] ?? 1);
                    
                    if (!empty($codeItem)) {
                        // Tìm hình ảnh từ GiftCodeItem nếu có
                        $image = null;
                        $stmt = $db->prepare("
                            SELECT TOP 1 g.Id, t.DuongDanFile
                            FROM GiftCodeItem g
                            LEFT JOIN TaiLieuDinhKem t ON t.Item_ID = g.Id AND t.LoaiTaiLieu = 'IconVP'
                            WHERE g.CodeItem = ? AND g.IsDelete = 0
                            ORDER BY t.CreatedDate DESC
                        ");
                        $stmt->execute([$codeItem]);
                        $imgResult = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($imgResult) {
                            $image = $imgResult['DuongDanFile'] ?? null;
                        }
                        
                        $items[] = [
                            'key' => $codeItem,
                            'name' => $itemName ?: $codeItem,
                            'quantity' => $quantity,
                            'displayName' => $quantity > 1 ? ($itemName ?: $codeItem) . " x ($quantity)" : ($itemName ?: $codeItem),
                            'image' => $image
                        ];
                    }
                }
            }
        } 
        // Fallback: đọc từ DsItem (cách cũ - tương thích ngược)
        else if (!empty($milestone['DsItem'])) {
            $itemIds = parseItemIds($milestone['DsItem']);
        
        if (!empty($itemIds)) {
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            
            $stmt = $db->prepare("
                SELECT Id, CodeItem, NameItem, quanlity
                FROM GiftCodeItem
                WHERE Id IN ($placeholders) AND IsDelete = 0
            ");
            $stmt->execute($itemIds);
            $giftItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($giftItems as $giftItem) {
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
                    'id' => $giftItem['Id'],
                    'key' => $giftItem['CodeItem'],
                    'name' => $itemName,
                    'quantity' => $quantity,
                    'displayName' => $quantity > 1 ? "$itemName x ($quantity)" : $itemName,
                    'image' => $image['DuongDanFile'] ?? null
                ];
                }
            }
        }
        
        $result[] = [
            'id' => $milestone['Id'],
            'price' => formatVND($milestone['Rank']),
            'priceValue' => (int)$milestone['Rank'],
            'description' => $milestone['Description'] ?? '',
            'isActive' => (bool)$milestone['IsActive'],
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

