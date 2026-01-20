<?php
/**
 * API: Get Seasons - Admin only
 * 
 * Workflow:
 * 1. Validate admin access
 * 2. Get all seasons
 * 3. Return results
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../../admin/auth_check.php';

try {
    require_once __DIR__ . '/../../../connection_manager.php';
    
    $db = ConnectionManager::getAccountDB();
    
    // Get all seasons
    $stmt = $db->query("
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
        ORDER BY StartDate DESC
    ");
    
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats for each season
    foreach ($seasons as &$season) {
        $statsStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_participants,
                SUM(TotalSpins) as total_spins
            FROM LuckyWheelSeasonLog
            WHERE SeasonId = ?
        ");
        $statsStmt->execute([$season['Id']]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        $season['total_participants'] = intval($stats['total_participants'] ?? 0);
        $season['total_spins'] = intval($stats['total_spins'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $seasons
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
