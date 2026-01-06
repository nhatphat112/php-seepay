<?php
/**
 * API: Kiểm tra user đã nhận vật phẩm chưa (Admin only)
 * GET /api/tichnap/check_user_rewards.php?username={username}&milestoneId={guid}
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "username": "username",
 *     "milestoneId": "guid",
 *     "claimedInLog": true,
 *     "claimedDate": "2025-01-10 10:00:00",
 *     "expectedItems": [...],
 *     "itemsInGame": [...]
 *   }
 * }
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
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $username = $_GET['username'] ?? '';
    $milestoneId = $_GET['milestoneId'] ?? '';
    
    if (empty($username)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Username is required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = ConnectionManager::getAccountDB();
    $shardDb = ConnectionManager::getShardDB();
    
    $result = [
        'username' => $username,
        'milestoneId' => $milestoneId,
        'claimedInLog' => false,
        'claimedDate' => null,
        'milestoneInfo' => null,
        'expectedItems' => [],
        'itemsInGame' => []
    ];
    
    // 1. Kiểm tra trong LogTichNap
    if (!empty($milestoneId)) {
        // Kiểm tra mốc cụ thể
        $stmt = $db->prepare("
            SELECT lt.*, st.Rank, st.Description, st.ItemsJson
            FROM LogTichNap lt
            INNER JOIN SilkTichNap st ON lt.IdTichNap = st.Id
            WHERE lt.CharName = ? 
                AND lt.IdTichNap = ?
                AND lt.Status = 1 
                AND lt.IsDelete = 0
            ORDER BY lt.CreatedDate DESC
        ");
        $stmt->execute([$username, $milestoneId]);
        $logEntry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($logEntry) {
            $result['claimedInLog'] = true;
            $result['claimedDate'] = $logEntry['CreatedDate'];
            $result['milestoneInfo'] = [
                'rank' => (int)$logEntry['Rank'],
                'description' => $logEntry['Description']
            ];
            
            // Parse ItemsJson để lấy danh sách items mong đợi
            if (!empty($logEntry['ItemsJson'])) {
                $itemsData = json_decode($logEntry['ItemsJson'], true);
                if (is_array($itemsData)) {
                    $result['expectedItems'] = $itemsData;
                }
            }
        }
        
        // Lấy thông tin mốc nếu chưa có
        if (!$result['milestoneInfo']) {
            $stmt = $db->prepare("
                SELECT Rank, Description, ItemsJson
                FROM SilkTichNap
                WHERE Id = ? AND IsDelete = 0
            ");
            $stmt->execute([$milestoneId]);
            $milestone = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($milestone) {
                $result['milestoneInfo'] = [
                    'rank' => (int)$milestone['Rank'],
                    'description' => $milestone['Description']
                ];
                
                if (!empty($milestone['ItemsJson'])) {
                    $itemsData = json_decode($milestone['ItemsJson'], true);
                    if (is_array($itemsData)) {
                        $result['expectedItems'] = $itemsData;
                    }
                }
            }
        }
    } else {
        // Lấy tất cả mốc đã nhận
        $stmt = $db->prepare("
            SELECT lt.*, st.Rank, st.Description, st.ItemsJson
            FROM LogTichNap lt
            INNER JOIN SilkTichNap st ON lt.IdTichNap = st.Id
            WHERE lt.CharName = ? 
                AND lt.Status = 1 
                AND lt.IsDelete = 0
            ORDER BY lt.CreatedDate DESC
        ");
        $stmt->execute([$username]);
        $logEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result['claimedMilestones'] = [];
        foreach ($logEntries as $entry) {
            $items = [];
            if (!empty($entry['ItemsJson'])) {
                $itemsData = json_decode($entry['ItemsJson'], true);
                if (is_array($itemsData)) {
                    $items = $itemsData;
                }
            }
            
            $result['claimedMilestones'][] = [
                'milestoneId' => $entry['IdTichNap'],
                'rank' => (int)$entry['Rank'],
                'description' => $entry['Description'],
                'claimedDate' => $entry['CreatedDate'],
                'expectedItems' => $items
            ];
        }
    }
    
    // 2. Kiểm tra trong _InstantItemDelivery (nếu có expectedItems)
    if (!empty($result['expectedItems'])) {
        // Lấy tất cả CharID của user này (một user có thể có nhiều nhân vật)
        // Note: Cần map username với CharName, giả sử username = CharName hoặc có bảng mapping
        // Ở đây giả sử username = CharName để đơn giản
        
        try {
            // Thử query với database prefix
            $stmt = $shardDb->prepare("
                SELECT CharID, CharName16
                FROM _Char
                WHERE CharName16 = ?
            ");
            $stmt->execute([$username]);
            $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($characters as $char) {
                $charID = $char['CharID'];
                $charName = $char['CharName16'];
                
                // Kiểm tra từng item trong _InstantItemDelivery
                foreach ($result['expectedItems'] as $expectedItem) {
                    $codeItem = $expectedItem['codeItem'] ?? '';
                    $expectedQuantity = (int)($expectedItem['quantity'] ?? 1);
                    
                    if (!empty($codeItem)) {
                        // Query với database prefix
                        $stmt = $shardDb->prepare("
                            SELECT SUM(Count) as totalQuantity, COUNT(*) as deliveryCount
                            FROM [SRO_VT_FILTER].[dbo].[_InstantItemDelivery]
                            WHERE CharID = ? AND CodeName = ?
                        ");
                        $stmt->execute([$charID, $codeItem]);
                        $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $receivedQuantity = (int)($itemResult['totalQuantity'] ?? 0);
                        $deliveryCount = (int)($itemResult['deliveryCount'] ?? 0);
                        
                        $result['itemsInGame'][] = [
                            'characterName' => $charName,
                            'charID' => $charID,
                            'itemCode' => $codeItem,
                            'itemName' => $expectedItem['name'] ?? $codeItem,
                            'expectedQuantity' => $expectedQuantity,
                            'receivedQuantity' => $receivedQuantity,
                            'deliveryCount' => $deliveryCount,
                            'status' => $receivedQuantity >= $expectedQuantity ? 'Đã nhận đủ' : ($receivedQuantity > 0 ? 'Đã nhận một phần' : 'Chưa nhận')
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Nếu không query được _InstantItemDelivery, chỉ trả về thông tin từ LogTichNap
            error_log("Error checking _InstantItemDelivery: " . $e->getMessage());
        }
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
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Check user rewards error: " . $e->getMessage());
}

