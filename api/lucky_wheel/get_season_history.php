<?php
/**
 * API: Get Season History - Last 3 seasons with top 5 leaderboard
 * 
 * Workflow:
 * 1. Get last 3 seasons
 * 2. Get top 5 for each season
 * 3. Return results
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    
    // Get season history
    $seasons = getSeasonHistory(3);
    
    // Format response
    $formattedSeasons = array_map(function($season) {
        return [
            'season_id' => intval($season['Id']),
            'season_name' => $season['SeasonName'],
            'season_type' => $season['SeasonType'],
            'start_date' => $season['StartDate'],
            'end_date' => $season['EndDate'],
            'status' => $season['Status'],
            'total_participants' => intval($season['total_participants'] ?? 0),
            'total_spins' => intval($season['total_spins'] ?? 0),
            'top_5' => array_map(function($item) {
                return [
                    'rank' => intval($item['Rank']),
                    'username' => $item['Username'],
                    'total_spins' => intval($item['TotalSpins'])
                ];
            }, $season['top_5'] ?? [])
        ];
    }, $seasons);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedSeasons
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
