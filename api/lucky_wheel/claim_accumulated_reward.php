<?php
/**
 * API: Nhận phần thưởng tích lũy (User)
 * Workflow tương tự tichnap - chỉ nhận được 1 lần duy nhất
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();

require_once __DIR__ . '/../../connection_manager.php';
require_once __DIR__ . '/../../includes/tichnap_helper.php';
require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';

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
    
    $accumulatedItemId = intval($input['accumulated_item_id'] ?? 0);
    $charNames = trim($input['char_names'] ?? '');
    
    // Lấy userJID từ session
    $userJID = (int)($_SESSION['user_id'] ?? 0);
    
    // Validate input
    if ($accumulatedItemId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'accumulated_item_id is required and must be greater than 0'
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
    
    $db = ConnectionManager::getAccountDB();
    $shardDb = ConnectionManager::getShardDB();
    
    // Nếu không có charNames, tự động lấy character đầu tiên của user
    if (empty($charNames)) {
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
    
    // 1. Kiểm tra và lấy thông tin accumulated item
    // First try to get from active items table
    $stmt = $db->prepare("
        SELECT Id, ItemName, ItemCode, Quantity, RequiredSpins
        FROM LuckyWheelAccumulatedItems
        WHERE Id = ? AND IsActive = 1
    ");
    $stmt->execute([$accumulatedItemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If item not found in active items (might be deleted), check user's log
    // This allows users to claim rewards even if admin deleted the item
    if (!$item) {
        // Check if user has already claimed this reward (from log)
        $logStmt = $db->prepare("
            SELECT TOP 1 ItemName, ItemCode, Quantity, RequiredSpins
            FROM LuckyWheelAccumulatedLog
            WHERE AccumulatedItemId = ? AND UserJID = ?
            ORDER BY ClaimedDate DESC
        ");
        $logStmt->execute([$accumulatedItemId, $userJID]);
        $logItem = $logStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($logItem) {
            // User has this in their log, but check if already claimed
            if (hasClaimedAccumulatedReward($userJID, $accumulatedItemId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Bạn đã nhận phần thưởng này rồi'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Use log data to reconstruct item info
            $item = [
                'Id' => $accumulatedItemId,
                'ItemName' => $logItem['ItemName'],
                'ItemCode' => $logItem['ItemCode'],
                'Quantity' => intval($logItem['Quantity']),
                'RequiredSpins' => intval($logItem['RequiredSpins'])
            ];
        } else {
            // Item doesn't exist and user doesn't have it in log
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Vật phẩm mốc quay không tồn tại'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    // 2. Tính tổng số vòng quay
    $totalSpins = getUserTotalSpins($userJID);
    
    // 3. Kiểm tra đã đạt mức chưa
    if ($totalSpins < intval($item['RequiredSpins'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bạn chưa đạt mức này. Tổng số vòng quay: ' . $totalSpins . ', cần: ' . $item['RequiredSpins'] . ' vòng'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 4. Kiểm tra đã nhận chưa (chỉ nhận được 1 lần duy nhất)
    if (hasClaimedAccumulatedReward($userJID, $accumulatedItemId)) {
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
    
    // 6. Bắt đầu transaction
    // Transaction ensures atomicity: either both item delivery and log insert succeed, or both fail
    $db->beginTransaction();
    
    try {
        // 7. Double-check claim status within transaction to prevent race condition
        // This ensures the reward hasn't been claimed between the initial check and transaction start
        if (hasClaimedAccumulatedReward($userJID, $accumulatedItemId)) {
            throw new Exception('Bạn đã nhận phần thưởng này rồi');
        }
        
        // 8. Thêm item vào game qua bảng _InstantItemDelivery
        $itemsToAdd = [[
            'codeName' => $item['ItemCode'],
            'codeItem' => $item['ItemCode'],
            'count' => intval($item['Quantity']),
            'quanlity' => intval($item['Quantity'])
        ]];
        
        $result = addMultipleItemsToCharacter(
            $charNames,
            $itemsToAdd,
            $shardDb,
            null,
            'SRO_VT_FILTER'
        );
        
        if (!$result['success'] || $result['failed'] > 0) {
            $errorMsg = 'Không thể thêm vật phẩm. ';
            if (!empty($result['errors'])) {
                $errorMsg .= implode('; ', $result['errors']);
            }
            throw new Exception($errorMsg);
        }
        
            // 9. Ghi log đã nhận (đảm bảo chỉ nhận được 1 lần)
            // Unique constraint on (UserJID, AccumulatedItemId) prevents double claim at database level
            $accumulatedLogId = null;
            try {
                $stmt = $db->prepare("
                    INSERT INTO LuckyWheelAccumulatedLog (
                        UserJID, AccumulatedItemId, ItemName, ItemCode, Quantity, RequiredSpins, TotalSpinsAtClaim, CharName, ClaimedDate
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, GETDATE()
                    )
                ");
                $stmt->execute([
                    $userJID,
                    $accumulatedItemId,
                    $item['ItemName'],
                    $item['ItemCode'],
                    intval($item['Quantity']),
                    intval($item['RequiredSpins']),
                    $totalSpins,
                    $charNames
                ]);
                
                // Get the inserted ID for item history log
                $accumulatedLogId = $db->lastInsertId();
            } catch (PDOException $e) {
                // Check if error is due to unique constraint violation (double claim attempt)
                if (strpos($e->getMessage(), 'IX_LuckyWheelAccumulatedLog_User_Item') !== false || 
                    strpos($e->getMessage(), 'UNIQUE') !== false ||
                    $e->getCode() == 23000) {
                    throw new Exception('Bạn đã nhận phần thưởng này rồi (duplicate claim prevented)');
                }
                throw $e;
            }
            
            // Log item history after successful claim
            $username = $_SESSION['username'] ?? '';
            logItemHistory(
                $userJID,
                $username,
                $item['ItemName'],
                $item['ItemCode'],
                intval($item['Quantity']),
                'accumulated_reward',
                $charNames,
                null, // RewardId (not applicable)
                $accumulatedLogId // AccumulatedLogId
            );
            
            // Commit transaction
            $db->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Đã nhận phần thưởng tích lũy thành công',
            'data' => [
                'item_name' => $item['ItemName'],
                'item_code' => $item['ItemCode'],
                'quantity' => intval($item['Quantity']),
                'char_name' => $charNames
            ]
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
    error_log("Claim accumulated reward error: " . $e->getMessage());
}
?>
