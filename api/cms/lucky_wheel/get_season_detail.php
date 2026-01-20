<?php
/**
 * API: Get Season Detail - Admin only
 * 
 * Workflow:
 * 1. Validate admin access
 * 2. Get season by ID
 * 3. Get top 5 leaderboard for season
 * 4. Return results
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../../admin/auth_check.php';

try {
    require_once __DIR__ . '/../../../includes/lucky_wheel_helper.php';
    require_once __DIR__ . '/../../../connection_manager.php';
    
    $seasonId = intval($_GET['season_id'] ?? 0);
    
    if ($seasonId <= 0) {
        throw new Exception('ID mùa không hợp lệ');
    }
    
    $db = ConnectionManager::getAccountDB();
    
    // Get season
    $stmt = $db->prepare("
        SELECT 
            Id,
            SeasonName,
            SeasonType,
            StartDate,
            EndDate,
            IsActive,
            Status,
            CreatedDate,
            UpdatedDate
        FROM LuckyWheelSeasons
        WHERE Id = ?
    ");
    $stmt->execute([$seasonId]);
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        throw new Exception('Mùa không tồn tại');
    }
    
    // Get leaderboard
    $leaderboard = getLeaderboard($seasonId, 5);
    
    // Get stats
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_participants,
            SUM(TotalSpins) as total_spins
        FROM LuckyWheelSeasonLog
        WHERE SeasonId = ?
    ");
    $statsStmt->execute([$seasonId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'season' => $season,
            'leaderboard' => $leaderboard,
            'stats' => [
                'total_participants' => intval($stats['total_participants'] ?? 0),
                'total_spins' => intval($stats['total_spins'] ?? 0)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
