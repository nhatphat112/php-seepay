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
    $userJID = (int)($input['userJID'] ?? 0);
    
    // Validate input
    if (empty($itemTichNap)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'itemTichNap is required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($charNames)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'charNames is required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($userJID <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid userJID'
        ], JSON_UNESCAPED_UNICODE);
        exit;
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
    
    // Check user ownership
    if ($userJID != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Forbidden. You can only claim rewards for your own account.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $username = $_SESSION['username'];
    $db = ConnectionManager::getAccountDB();
    $shardDb = ConnectionManager::getShardDB();
    
    // 0. Kiểm tra tính năng có được bật không
    $stmt = $db->prepare("
        SELECT TOP 1 FeatureEnabled
        FROM TichNapConfig
        ORDER BY UpdatedDate DESC
    ");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || !$config['FeatureEnabled']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Tính năng nạp tích lũy đang tạm thời tắt'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 1. Kiểm tra và lấy thông tin mốc nạp (chỉ lấy mốc đang active)
    $stmt = $db->prepare("
        SELECT Id, Rank, DsItem, Description
        FROM SilkTichNap
        WHERE Id = ? AND IsDelete = 0 AND IsActive = 1
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
    $itemIds = parseItemIds($milestone['DsItem']);
    
    if (empty($itemIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Mốc nạp này không có phần thưởng'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    
    $stmt = $db->prepare("
        SELECT CodeItem, quanlity, NameItem
        FROM GiftCodeItem
        WHERE Id IN ($placeholders) AND IsDelete = 0
    ");
    $stmt->execute($itemIds);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy vật phẩm phần thưởng'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 7. Bắt đầu transaction để đảm bảo tính nhất quán
    $db->beginTransaction();
    
    try {
        // 8. Thêm item vào game (gọi stored procedure cho từng item)
        $addedItems = [];
        foreach ($items as $item) {
            $success = addItemToCharacter(
                $charNames,
                $item['CodeItem'],
                (int)$item['quanlity'],
                $shardDb
            );
            
            if ($success) {
                $addedItems[] = [
                    'codeItem' => $item['CodeItem'],
                    'quanlity' => (int)$item['quanlity']
                ];
            } else {
                throw new Exception("Không thể thêm item: " . $item['CodeItem']);
            }
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

