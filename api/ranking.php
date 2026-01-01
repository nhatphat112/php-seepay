<?php
/**
 * Ranking API
 * Returns ranking data based on type
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

$type = $_GET['type'] ?? 'level';

try {
    $shardDb = ConnectionManager::getShardDB();
    
    switch ($type) {
        case 'level':
            // Top players by level
            $stmt = $shardDb->prepare("
                SELECT TOP 50 
                    CharName16 as name, 
                    CurLevel as level,
                    ExpOffset as exp
                FROM _Char
                WHERE CharName16 IS NOT NULL AND CharName16 != ''
                ORDER BY CurLevel DESC, ExpOffset DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'guild':
            // Top guilds - check if table exists first
            try {
                $stmt = $shardDb->prepare("
                    SELECT TOP 50
                        Name as name,
                        Lvl as level,
                        (SELECT COUNT(*) FROM _GuildMember WHERE GuildID = _Guild.ID) as members,
                        GatheredSP as points
                    FROM _Guild
                    WHERE Name IS NOT NULL AND Name != ''
                    ORDER BY GatheredSP DESC, Lvl DESC
                ");
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Guild table might not exist or have different structure
                $data = [];
            }
            break;
            
        case 'pvp':
            // Top PvP players
            $stmt = $shardDb->prepare("
                SELECT TOP 50
                    CharName16 as name,
                    ISNULL(PKCount, 0) as kills,
                    ISNULL(DiedCount, 0) as deaths,
                    (ISNULL(PKCount, 0) - ISNULL(DiedCount, 0)) as ratio
                FROM _Char
                WHERE CharName16 IS NOT NULL 
                    AND CharName16 != ''
                    AND ISNULL(PKCount, 0) > 0
                ORDER BY PKCount DESC, ratio DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            $data = [];
    }
    
    // Return empty array if no data
    if (empty($data)) {
        $data = [];
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>

