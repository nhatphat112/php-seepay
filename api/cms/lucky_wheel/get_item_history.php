<?php
/**
 * API: Lấy lịch sử nhận vật phẩm từ vòng quay may mắn (Admin)
 * 
 * Returns item history with:
 * - Item name and quantity
 * - Source (lucky_wheel or accumulated_reward)
 * - Receive time
 * - User information
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../../admin/auth_check.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Admin login required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use GET.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../../connection_manager.php';
    
    $db = ConnectionManager::getAccountDB();
    
    // Get query parameters for filtering
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 50;
    $offset = ($page - 1) * $limit;
    
    // Filter parameters
    $userJID = isset($_GET['user_jid']) && $_GET['user_jid'] !== '' ? intval($_GET['user_jid']) : null;
    $username = isset($_GET['username']) && trim($_GET['username']) !== '' ? trim($_GET['username']) : null;
    $source = isset($_GET['source']) && trim($_GET['source']) !== '' ? trim($_GET['source']) : null;
    $itemName = isset($_GET['item_name']) && trim($_GET['item_name']) !== '' ? trim($_GET['item_name']) : null;
    $startDate = isset($_GET['start_date']) && trim($_GET['start_date']) !== '' ? trim($_GET['start_date']) : null;
    $endDate = isset($_GET['end_date']) && trim($_GET['end_date']) !== '' ? trim($_GET['end_date']) : null;
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    $paramIndex = 1;
    
    if ($userJID !== null) {
        $whereConditions[] = "h.UserJID = ?";
        $params[] = $userJID;
    }
    
    if ($username !== null) {
        $whereConditions[] = "h.Username LIKE ?";
        $params[] = '%' . $username . '%';
    }
    
    if ($source !== null && in_array($source, ['lucky_wheel', 'accumulated_reward'])) {
        $whereConditions[] = "h.Source = ?";
        $params[] = $source;
    }
    
    if ($itemName !== null) {
        $whereConditions[] = "h.ItemName LIKE ?";
        $params[] = '%' . $itemName . '%';
    }
    
    if ($startDate !== null) {
        $whereConditions[] = "h.ReceivedDate >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate !== null) {
        $whereConditions[] = "h.ReceivedDate <= ?";
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM LuckyWheelItemHistory h $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = intval($countStmt->fetch(PDO::FETCH_ASSOC)['total']);
    
    // Note: SQL Server requires OFFSET and FETCH NEXT to be integers
    // We need to embed them directly in SQL (safely, since we've already validated them)
    $offset = (int)$offset;
    $limit = (int)$limit;
    
    // Get paginated results
    $sql = "
        SELECT 
            h.Id,
            h.UserJID,
            h.Username,
            h.ItemName,
            h.ItemCode,
            h.Quantity,
            h.Source,
            h.CharName,
            h.ReceivedDate,
            h.RewardId,
            h.AccumulatedLogId
        FROM LuckyWheelItemHistory h
        $whereClause
        ORDER BY h.ReceivedDate DESC
        OFFSET $offset ROWS
        FETCH NEXT $limit ROWS ONLY
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results
    $formattedItems = [];
    foreach ($items as $item) {
        $formattedItems[] = [
            'id' => intval($item['Id']),
            'user_jid' => intval($item['UserJID']),
            'username' => $item['Username'],
            'item_name' => $item['ItemName'],
            'item_code' => $item['ItemCode'],
            'quantity' => intval($item['Quantity']),
            'source' => $item['Source'],
            'source_text' => $item['Source'] === 'lucky_wheel' ? 'Vòng Quay May Mắn' : 'Phần Thưởng Tích Lũy',
            'char_name' => $item['CharName'],
            'received_date' => $item['ReceivedDate'],
            'received_date_formatted' => date('d/m/Y H:i:s', strtotime($item['ReceivedDate'])),
            'reward_id' => $item['RewardId'] ? intval($item['RewardId']) : null,
            'accumulated_log_id' => $item['AccumulatedLogId'] ? intval($item['AccumulatedLogId']) : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $formattedItems,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Get item history error: " . $e->getMessage());
}

?>
