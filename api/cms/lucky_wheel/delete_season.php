<?php
/**
 * API: Delete Season - Admin only
 * 
 * Requirement:
 * - Only allow creating and deleting seasons. Editing is disabled.
 * - When deleting a season, also delete all accumulated season data for users
 *   (LuckyWheelSeasonLog, accumulated rewards log if any)
 *
 * Expected request: POST JSON { "id": <season_id> }
 */
	error_reporting(E_ALL);
	ini_set('display_errors', 0);
	ini_set('log_errors', 1);

	header('Content-Type: application/json; charset=utf-8');
	header('Access-Control-Allow-Origin: *');

	session_start();

	require_once __DIR__ . '/../../../admin/auth_check.php';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	    http_response_code(405);
	    echo json_encode([
	        'success' => false,
	        'error' => 'Method not allowed. Use POST.'
	    ], JSON_UNESCAPED_UNICODE);
	    exit;
	}

	try {
	    require_once __DIR__ . '/../../../connection_manager.php';
	    $input = json_decode(file_get_contents('php://input'), true);
	    $seasonId = intval($input['id'] ?? 0);
	    if ($seasonId <= 0) {
	        throw new Exception('ID mùa không hợp lệ');
	    }

	    $db = ConnectionManager::getAccountDB();

	    // Check if season exists
	    $check = $db->prepare("SELECT Id, SeasonName FROM LuckyWheelSeasons WHERE Id = ?");
	    $check->execute([$seasonId]);
	    $season = $check->fetch(PDO::FETCH_ASSOC);
	    if (!$season) {
	        throw new Exception('Mùa không tồn tại');
	    }

	    // Start transaction to ensure atomic delete
	    $db->beginTransaction();
	    try {
	        // Remove SeasonId reference from LuckyWheelLog first to satisfy FK constraint
        $db->prepare("UPDATE LuckyWheelLog SET SeasonId = NULL WHERE SeasonId = ?")->execute([$seasonId]);

        // Delete accumulated season log
        $db->prepare("DELETE FROM LuckyWheelSeasonLog WHERE SeasonId = ?")->execute([$seasonId]);

        // Finally delete season itself
        $db->prepare("DELETE FROM LuckyWheelSeasons WHERE Id = ?")->execute([$seasonId]);
	        $db->prepare("DELETE FROM LuckyWheelSeasons WHERE Id = ?")->execute([$seasonId]);

	        // Delete accumulated season log
	        $db->prepare("DELETE FROM LuckyWheelSeasonLog WHERE SeasonId = ?")->execute([$seasonId]);

	        // Also remove SeasonId reference from LuckyWheelLog rows for consistency (set NULL)
	        $db->prepare("UPDATE LuckyWheelLog SET SeasonId = NULL WHERE SeasonId = ?")->execute([$seasonId]);

	        $db->commit();
	    } catch (Exception $inner) {
	        $db->rollBack();
	        throw $inner;
	    }

	    echo json_encode([
	        'success' => true,
	        'message' => 'Đã xoá mùa và dữ liệu liên quan thành công'
	    ], JSON_UNESCAPED_UNICODE);
	} catch (Exception $e) {
	    http_response_code(400);
	    echo json_encode([
	        'success' => false,
	        'error' => $e->getMessage()
	    ], JSON_UNESCAPED_UNICODE);
	}
?>