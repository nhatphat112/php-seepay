<?php
/**
 * API: Tìm kiếm vật phẩm (Admin only)
 * GET /api/tichnap/search_items.php?keyword=xxx&limit=20
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "id": "guid-1",
 *       "codeItem": "ITEM_CODE",
 *       "nameItem": "Item Name",
 *       "quanlity": 10,
 *       "image": "https://..."
 *     }
 *   ]
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../connection_manager.php';

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
    
    // Get parameters
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    
    // Build WHERE clause
    $whereConditions = ["IsDelete = 0"];
    $params = [];
    
    if (!empty($keyword)) {
        $whereConditions[] = "(CodeItem LIKE ? OR NameItem LIKE ?)";
        $searchTerm = '%' . $keyword . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM GiftCodeItem $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get items with pagination
    $sql = "SELECT Id, CodeItem, NameItem, quanlity
            FROM GiftCodeItem
            $whereClause
            ORDER BY CreatedDate DESC
            OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get images for each item
    $result = [];
    foreach ($items as $item) {
        // Get image (IconVP)
        $imageStmt = $db->prepare("
            SELECT DuongDanFile
            FROM TaiLieuDinhKem
            WHERE Item_ID = ? AND LoaiTaiLieu = 'IconVP'
            ORDER BY CreatedDate DESC
        ");
        $imageStmt->execute([$item['Id']]);
        $image = $imageStmt->fetch(PDO::FETCH_ASSOC);
        
        $result[] = [
            'id' => $item['Id'],
            'codeItem' => $item['CodeItem'],
            'nameItem' => $item['NameItem'] ?: $item['CodeItem'],
            'quanlity' => (int)$item['quanlity'],
            'image' => $image['DuongDanFile'] ?? null
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result,
        'pagination' => [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}

