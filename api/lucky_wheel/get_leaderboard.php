<?php
/**
 * API: Get Leaderboard - Top 5 players in current season
 * 
 * Workflow:
 * 1. Get current active season
 * 2. Get top 5 leaderboard
 * 3. Return results
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../includes/lucky_wheel_helper.php';
    
    // Get current season
    $season = getCurrentSeason();
    
    if (!$season) {
        echo json_encode([
            'success' => true,
            'data' => [
                'season' => null,
                'leaderboard' => []
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Get leaderboard
    $leaderboard = getLeaderboard($season['Id'], 5);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'season' => [
                'id' => intval($season['Id']),
                'name' => $season['SeasonName'],
                'type' => $season['SeasonType'],
                'start_date' => $season['StartDate'],
                'end_date' => $season['EndDate']
            ],
            'leaderboard' => array_map(function($item) {
                return [
                    'rank' => intval($item['Rank']),
                    'username' => $item['Username'],
                    'total_spins' => intval($item['TotalSpins'])
                ];
            }, $leaderboard)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
