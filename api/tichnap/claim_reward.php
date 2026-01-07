<?php
/**
 * API: Nhận phần thưởng mốc nạp
 * POST /api/tichnap/claim_reward.php
 * 
 * Request:
 * {
 *   "itemTichNap": "guid-1",
 *   "charNames": "CharacterName",
 *   "userJID": 12345
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Đã nhận phần thưởng thành công",
 *   "data": [...]
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once __DIR__ . '/../../connection_manager.php';
require_once __DIR__ . '/../../includes/tichnap_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login first.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $itemTichNap = trim($input['itemTichNap'] ?? '');
    $charNames = trim($input['charNames'] ?? '');
    
    // Lấy userJID từ session (không lấy từ input để đảm bảo security)
    $userJID = (int)($_SESSION['user_id'] ?? 0);
    
    // Validate input
    if (empty($itemTichNap)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'itemTichNap is required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($userJID <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid user session. Please login again.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Nếu không có charNames, tự động lấy character đầu tiên của user
    if (empty($charNames)) {
        $db = ConnectionManager::getAccountDB();
        $shardDb = ConnectionManager::getShardDB();
        $charNames = getFirstCharacterNameFromJID($userJID, $shardDb, $db);
        
        if (empty($charNames)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy nhân vật. Vui lòng tạo nhân vật trong game trước.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    // Validate GUID format
    if (!isValidGuid($itemTichNap)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid itemTichNap GUID format'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $username = $_SESSION['username'];
    $db = ConnectionManager::getAccountDB();
    
    // Lấy shardDb nếu chưa có (nếu đã lấy ở trên thì không cần lấy lại)
    if (!isset($shardDb)) {
    $shardDb = ConnectionManager::getShardDB();
    }
    
    // 0. Kiểm tra tính năng có đang hoạt động không (bật và trong thời gian sự kiện)
    $featureStatus = checkTichNapFeatureStatus($db);
    
    if (!$featureStatus['enabled']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => $featureStatus['message'] ?? 'Tính năng nạp tích lũy đang tạm thời tắt'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!$featureStatus['inTimeRange']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => $featureStatus['message'] ?? 'Sự kiện nạp tích lũy hiện không khả dụng'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 1. Kiểm tra và lấy thông tin mốc nạp (tất cả mốc đều active mặc định)
    $stmt = $db->prepare("
        SELECT Id, Rank, DsItem, ItemsJson, Description
        FROM SilkTichNap
        WHERE Id = ? AND IsDelete = 0
    ");
    $stmt->execute([$itemTichNap]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$milestone) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Mốc nạp không tồn tại hoặc đã bị xóa'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 2. Tính tổng tiền đã nạp
    $totalMoney = getTotalMoneyFromOrders($userJID, $db);
    
    // 3. Kiểm tra đã đạt mốc chưa
    if ($totalMoney < $milestone['Rank']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bạn chưa đạt mốc nạp này. Tổng tiền đã nạp: ' . formatVND($totalMoney) . ', cần: ' . formatVND($milestone['Rank'])
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 4. Kiểm tra đã nhận chưa
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM LogTichNap
        WHERE CharName = ? AND IdTichNap = ? AND Status = 1 AND IsDelete = 0
    ");
    $stmt->execute([$username, $itemTichNap]);
    $claimed = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($claimed['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bạn đã nhận phần thưởng này rồi'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 5. Kiểm tra nhân vật tồn tại
    if (!checkCharacterExists($charNames, $shardDb)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Nhân vật không tồn tại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 6. Lấy danh sách item cần trao
    $items = [];
    
    // Ưu tiên đọc từ ItemsJson (cách mới)
    if (!empty($milestone['ItemsJson'])) {
        $itemsData = json_decode($milestone['ItemsJson'], true);
        if (is_array($itemsData) && !empty($itemsData)) {
            foreach ($itemsData as $itemData) {
                $codeItem = trim($itemData['codeItem'] ?? '');
                $quantity = (int)($itemData['quantity'] ?? 1);
                $name = trim($itemData['name'] ?? '');
                
                if (!empty($codeItem) && $quantity > 0) {
                    $items[] = [
                        'CodeItem' => $codeItem,
                        'quanlity' => $quantity,
                        'NameItem' => $name ?: $codeItem
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
        SELECT CodeItem, quanlity, NameItem
        FROM GiftCodeItem
        WHERE Id IN ($placeholders) AND IsDelete = 0
    ");
    $stmt->execute($itemIds);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    if (empty($items)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Mốc nạp này không có phần thưởng'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 7. Bắt đầu transaction để đảm bảo tính nhất quán
    $db->beginTransaction();
    
    try {
        // 8. Thêm item vào game qua bảng _InstantItemDelivery
        // Chuẩn bị dữ liệu items để thêm cùng lúc
        $itemsToAdd = [];
        foreach ($items as $item) {
            $itemsToAdd[] = [
                'codeName' => $item['CodeItem'],
                'codeItem' => $item['CodeItem'],  // Alias để tương thích
                'count' => (int)$item['quanlity'],
                'quanlity' => (int)$item['quanlity']  // Alias để tương thích
            ];
        }
        
        // Sử dụng hàm addMultipleItemsToCharacter để thêm tất cả items cùng lúc
        // Hàm này sẽ:
        // 1. Lấy CharID từ CharName trong SRO_VT_SHARD.dbo._Char (chỉ 1 lần)
        // 2. Insert tất cả items vào SRO_VT_FILTER.dbo._InstantItemDelivery
        // Refactor theo SQL script: INSERT INTO SRO_VT_FILTER.dbo.[_InstantItemDelivery] ...
        $result = addMultipleItemsToCharacter(
            $charNames,
            $itemsToAdd,
            $shardDb,
            'SRO_VT_FILTER'  // Tên database FILTER
        );
        
        if (!$result['success'] || $result['failed'] > 0) {
            $errorMsg = 'Không thể thêm một số vật phẩm. ';
            if (!empty($result['errors'])) {
                $errorMsg .= implode('; ', $result['errors']);
            }
            throw new Exception($errorMsg);
        }
        
        // Chuẩn bị danh sách items đã thêm để trả về
        $addedItems = [];
        foreach ($items as $item) {
                $addedItems[] = [
                    'codeItem' => $item['CodeItem'],
                'quanlity' => (int)$item['quanlity'],
                'name' => $item['NameItem'] ?? $item['CodeItem']
                ];
        }
        
        // 9. Ghi log đã nhận
        $stmt = $db->prepare("
            INSERT INTO LogTichNap (
                Id, CharName, IdTichNap, MaxPrice, Status, CreatedDate
            ) VALUES (
                NEWID(), ?, ?, ?, 1, GETDATE()
            )
        ");
        $stmt->execute([
            $username,
            $itemTichNap,
            $milestone['Rank']
        ]);
        
        // Commit transaction
        $db->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Đã nhận phần thưởng thành công',
            'data' => $addedItems
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Claim reward error: " . $e->getMessage());
}

