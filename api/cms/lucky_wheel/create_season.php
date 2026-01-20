<?php
/**
 * API: Create Season - Admin only
 * 
 * Workflow:
 * 1. Validate admin access
 * 2. Validate input (season name, dates)
 * 3. Create season
 * 4. Return result
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
    
    $seasonName = trim($input['season_name'] ?? '');
    // Season type is fixed to DAY (UI no longer exposes it).
    $seasonType = strtoupper(trim($input['season_type'] ?? 'DAY'));
    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    
    // Validation
    if (empty($seasonName)) {
        throw new Exception('Tên mùa không được để trống');
    }
    
    // Keep validation strict even though the UI defaults to DAY.
    if (!in_array($seasonType, ['WEEK', 'DAY'])) {
        $seasonType = 'DAY';
    }
    
    if (empty($startDate)) {
        throw new Exception('Ngày bắt đầu không được để trống');
    }
    
    // Parse dates
    $startDateTime = new DateTime($startDate);
    $endDateTime = $endDate ? new DateTime($endDate) : null;
    
    // Auto calculate end date if not provided
    if (!$endDateTime) {
        $endDateTime = clone $startDateTime;
        if ($seasonType === 'WEEK') {
            $endDateTime->modify('+7 days');
        } else {
            $endDateTime->modify('+1 day');
        }
    }
    
    // Validate dates
    if ($endDateTime <= $startDateTime) {
        throw new Exception('Ngày kết thúc phải sau ngày bắt đầu');
    }

    // Validate no time-overlap with existing seasons
    $db = ConnectionManager::getAccountDB();

    $overlapStmt = $db->prepare("
        SELECT TOP 1 StartDate, EndDate, SeasonName
        FROM LuckyWheelSeasons
        WHERE 
            -- Overlap condition: existing starts before new end AND existing ends after new start
            StartDate < ? AND EndDate > ?
        ORDER BY StartDate DESC
    ");
    $overlapStmt->execute([
        $endDateTime->format('Y-m-d H:i:s'),
        $startDateTime->format('Y-m-d H:i:s')
    ]);
    $overlap = $overlapStmt->fetch(PDO::FETCH_ASSOC);

    if ($overlap) {
        $suggestedStart = (new DateTime($overlap['EndDate']))->modify('+1 day');
        throw new Exception(
            'Mùa mới đang trùng thời gian với mùa ' . ($overlap['SeasonName'] ?? '') .
            '. Vui lòng chọn thời gian bắt đầu sau ' . $suggestedStart->format('d/m/Y H:i')
        );
    }
    
    // Check if season name already exists
    $checkStmt = $db->prepare("SELECT COUNT(*) as cnt FROM LuckyWheelSeasons WHERE SeasonName = ?");
    $checkStmt->execute([$seasonName]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (intval($exists['cnt']) > 0) {
        throw new Exception('Tên mùa đã tồn tại');
    }
    
    // Insert season
    $insertStmt = $db->prepare("
        INSERT INTO LuckyWheelSeasons 
        (SeasonName, SeasonType, StartDate, EndDate, Status, IsActive)
        VALUES (?, ?, ?, ?, 'PENDING', 0)
    ");
    
    $insertStmt->execute([
        $seasonName,
        $seasonType,
        $startDateTime->format('Y-m-d H:i:s'),
        $endDateTime->format('Y-m-d H:i:s')
    ]);
    
    $seasonId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã tạo mùa mới thành công',
        'data' => [
            'id' => intval($seasonId),
            'season_name' => $seasonName,
            'season_type' => $seasonType,
            'start_date' => $startDateTime->format('Y-m-d H:i:s'),
            'end_date' => $endDateTime->format('Y-m-d H:i:s')
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
