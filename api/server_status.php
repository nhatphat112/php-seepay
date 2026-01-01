<?php
/**
 * Server Status API
 * Returns current server status and online players
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../connection_manager.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'offline',
        'error' => 'Failed to load connection manager',
        'message' => $e->getMessage()
    ]);
    exit;
}

try {
    $shardDb = ConnectionManager::getShardDB();
    
    // Get online players count
    $onlinePlayers = 0;
    try {
        $stmt = $shardDb->query("
            SELECT COUNT(DISTINCT UserJID) as online_count
            FROM _User
            WHERE Status = 1
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $onlinePlayers = intval($result['online_count'] ?? 0);
    } catch (Exception $e) {
        // _User table might not exist or have different structure
        $onlinePlayers = 0;
    }
    
    // Get total characters
    $totalChars = 0;
    try {
        $stmt = $shardDb->query("SELECT COUNT(*) as total FROM _Char");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalChars = intval($result['total'] ?? 0);
    } catch (Exception $e) {
        // _Char table might not exist
        $totalChars = 0;
    }
    
    // Get connection status
    $connStatus = ConnectionManager::getConnectionStatus();
    
    echo json_encode([
        'status' => 'online',
        'online_players' => $onlinePlayers,
        'total_characters' => $totalChars,
        'uptime' => $connStatus['uptime_seconds'] ?? 0,
        'server_time' => date('Y-m-d H:i:s'),
        'connections' => $connStatus['connections'] ?? []
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'offline',
        'error' => 'Cannot connect to server',
        'message' => $e->getMessage()
    ]);
}
?>

