<?php
/**
 * Character Info API
 * Returns detailed character information
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

try {
    require_once '../connection_manager.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load connection manager',
        'message' => $e->getMessage()
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$charId = $_GET['char_id'] ?? null;

if (!$charId) {
    http_response_code(400);
    echo json_encode(['error' => 'Character ID required']);
    exit();
}

try {
    $shardDb = ConnectionManager::getShardDB();
    
    // Get character details
    $stmt = $shardDb->prepare("
        SELECT 
            CharID,
            CharName16 as name,
            CurLevel as level,
            ExpOffset as exp,
            RemainGold as gold,
            RemainSkillPoint as skill_points,
            HP as current_hp,
            MP as current_mp,
            STR as strength,
            INT as intelligence,
            Job as job_type,
            LatestUpdateDate as last_login
        FROM _Char
        WHERE CharID = ? AND UserJID = ?
    ");
    
    $stmt->execute([$charId, $_SESSION['user_id']]);
    $character = $stmt->fetch();
    
    if (!$character) {
        http_response_code(404);
        echo json_encode(['error' => 'Character not found']);
        exit();
    }
    
    // Get inventory count
    $stmt = $shardDb->prepare("
        SELECT COUNT(*) as item_count
        FROM _Inventory
        WHERE CharID = ?
    ");
    $stmt->execute([$charId]);
    $inventoryResult = $stmt->fetch();
    $character['inventory_count'] = $inventoryResult['item_count'] ?? 0;
    
    echo json_encode($character);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>

