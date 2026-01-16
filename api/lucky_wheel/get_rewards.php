<?php
/**
 * API: Lấy danh sách phần thưởng đã trúng (User)
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

try {
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    
    $userJID = $_SESSION['user_id'];
    $status = $_GET['status'] ?? 'pending'; // pending, claimed, all
    
    $accountDb = ConnectionManager::getAccountDB();
    
    $sql = "
        SELECT 
            Id,
            ItemName,
            ItemCode,
            Quantity,
            IsRare,
            WonDate,
            ClaimedDate,
            Status
        FROM LuckyWheelRewards
        WHERE UserJID = ?
    ";
    
    if ($status !== 'all') {
        $sql .= " AND Status = ?";
        $params = [$userJID, $status];
    } else {
        $params = [$userJID];
    }
    
    $sql .= " ORDER BY WonDate DESC";
    
    $stmt = $accountDb->prepare($sql);
    $stmt->execute($params);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results
    $results = [];
    foreach ($rewards as $reward) {
        $results[] = [
            'id' => intval($reward['Id']),
            'item_name' => $reward['ItemName'],
            'item_code' => $reward['ItemCode'],
            'quantity' => intval($reward['Quantity']),
            'is_rare' => (bool)$reward['IsRare'],
            'won_date' => $reward['WonDate'],
            'claimed_date' => $reward['ClaimedDate'],
            'status' => $reward['Status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
