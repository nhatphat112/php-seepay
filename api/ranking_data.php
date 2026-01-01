<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../connection_manager.php';

$type = $_GET['type'] ?? 'level';
$limit = 25;

try {
    $db = ConnectionManager::getShardDB();
    $data = [];
    
    switch($type) {
        case 'ch': // Chinese characters
            $stmt = $db->query("
                SELECT TOP $limit
                    CharName16,
                    CurLevel,
                    ExpOffset,
                    RemainSkillPoint,
                    Strength,
                    Intellect
                FROM _Char
                WHERE RefObjID < 10000
                ORDER BY CurLevel DESC, ExpOffset DESC
            ");
            $data = $stmt->fetchAll();
            break;
            
        case 'eu': // European characters
            $stmt = $db->query("
                SELECT TOP $limit
                    CharName16,
                    CurLevel,
                    ExpOffset,
                    RemainSkillPoint,
                    Strength,
                    Intellect
                FROM _Char
                WHERE RefObjID > 10000
                ORDER BY CurLevel DESC, ExpOffset DESC
            ");
            $data = $stmt->fetchAll();
            break;
            
        case 'trader': // Trader ranking
            $stmt = $db->query("
                SELECT TOP $limit
                    c.CharName16,
                    ct.Level,
                    ct.Exp,
                    c.NickName16
                FROM _Char c
                INNER JOIN _CharTrijob ct ON c.CharID = ct.CharID
                WHERE ct.JobType = 1
                ORDER BY ct.Level DESC, ct.Exp DESC
            ");
            $data = $stmt->fetchAll();
            break;
            
        case 'hunter': // Hunter ranking
            $stmt = $db->query("
                SELECT TOP $limit
                    c.CharName16,
                    ct.Level,
                    ct.Exp,
                    c.NickName16
                FROM _Char c
                INNER JOIN _CharTrijob ct ON c.CharID = ct.CharID
                WHERE ct.JobType = 2
                ORDER BY ct.Level DESC, ct.Exp DESC
            ");
            $data = $stmt->fetchAll();
            break;
            
        case 'thief': // Thief ranking
            $stmt = $db->query("
                SELECT TOP $limit
                    c.CharName16,
                    ct.Level,
                    ct.Exp,
                    c.NickName16
                FROM _Char c
                INNER JOIN _CharTrijob ct ON c.CharID = ct.CharID
                WHERE ct.JobType = 3
                ORDER BY ct.Level DESC, ct.Exp DESC
            ");
            $data = $stmt->fetchAll();
            break;
            
        case 'gold': // Gold ranking
            $stmt = $db->query("
                SELECT TOP $limit
                    CharName16,
                    CurLevel,
                    ExpOffset,
                    RemainGold
                FROM _Char
                ORDER BY RemainGold DESC, CurLevel DESC, ExpOffset DESC
            ");
            $data = $stmt->fetchAll();
            break;
            
        default: // Level ranking (all)
            $stmt = $db->query("
                SELECT TOP $limit
                    CharName16,
                    CurLevel,
                    ExpOffset,
                    RemainGold,
                    RefObjID
                FROM _Char
                ORDER BY CurLevel DESC, ExpOffset DESC
            ");
            $data = $stmt->fetchAll();
            break;
    }
    
    echo json_encode([
        'success' => true,
        'type' => $type,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

