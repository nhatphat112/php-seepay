<?php
/**
 * API: Nhận phần thưởng (User)
 * Sử dụng workflow của tichnap để nhận vật phẩm
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../includes/auth_helper.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    require_once __DIR__ . '/../../includes/tichnap_helper.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    $rewardId = intval($input['reward_id'] ?? $_POST['reward_id'] ?? 0);
    $charName = trim($input['char_name'] ?? $_POST['char_name'] ?? '');
    
    if ($rewardId <= 0) {
        throw new Exception('ID phần thưởng không hợp lệ');
    }
    
    $userJID = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $accountDb = ConnectionManager::getAccountDB();
    $shardDb = ConnectionManager::getShardDB();
    
    // Get reward info
    $stmt = $accountDb->prepare("
        SELECT 
            Id,
            UserJID,
            ItemCode,
            Quantity,
            ItemName,
            Status
        FROM LuckyWheelRewards
        WHERE Id = ? AND UserJID = ? AND Status = 'pending'
    ");
    $stmt->execute([$rewardId, $userJID]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reward) {
        throw new Exception('Phần thưởng không tồn tại hoặc đã được nhận');
    }
    
    // Get character name if not provided
    if (empty($charName)) {
        $charName = getFirstCharacterNameFromJID($userJID, $shardDb, $accountDb);
        
        if (empty($charName)) {
            throw new Exception('Không tìm thấy nhân vật. Vui lòng tạo nhân vật trong game trước.');
        }
    }
    
    // Check character exists
    if (!checkCharacterExists($charName, $shardDb)) {
        throw new Exception('Nhân vật không tồn tại');
    }
    
    // Use tichnap workflow to give item
    $accountDb->beginTransaction();
    
    try {
        // Prepare item data for addMultipleItemsToCharacter
        $itemsToAdd = [[
            'codeName' => $reward['ItemCode'],
            'codeItem' => $reward['ItemCode'],
            'count' => intval($reward['Quantity']),
            'quanlity' => intval($reward['Quantity'])
        ]];
        
        // Add item to character via InstantItemDelivery
        $result = addMultipleItemsToCharacter(
            $charName,
            $itemsToAdd,
            $shardDb,
            null,  // $filterDb = null (dùng shardDb với database prefix)
            'SRO_VT_FILTER'  // $filterDatabase
        );
        
        if (!$result['success'] || $result['failed'] > 0) {
            $errorMsg = 'Không thể thêm vật phẩm. ';
            if (!empty($result['errors'])) {
                $errorMsg .= implode('; ', $result['errors']);
            }
            throw new Exception($errorMsg);
        }
        
        // Mark as claimed
        $updateStmt = $accountDb->prepare("
            UPDATE LuckyWheelRewards
            SET Status = 'claimed', ClaimedDate = GETDATE()
            WHERE Id = ?
        ");
        $updateStmt->execute([$rewardId]);
        
        $accountDb->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã nhận phần thưởng thành công',
            'data' => [
                'item_code' => $reward['ItemCode'],
                'item_name' => $reward['ItemName'],
                'quantity' => intval($reward['Quantity']),
                'char_name' => $charName
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $accountDb->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
