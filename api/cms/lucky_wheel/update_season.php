<?php
/**
 * API: Update Season - Admin only
 * 
 * Workflow:
 * 1. Validate admin access
 * 2. Validate input
 * 3. Update season (only PENDING or ENDED seasons)
 * 4. Return result
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../../../admin/auth_check.php';

// Editing season is disabled permanently (requirement: only create and delete seasons)
http_response_code(400);
echo json_encode([
    'success' => false,
    'error' => 'Chức năng chỉnh sửa mùa đã bị vô hiệu hoá. Vui lòng xoá mùa cũ và tạo mùa mới.'
], JSON_UNESCAPED_UNICODE);
exit;

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
    $seasonName = trim($input['season_name'] ?? '');
    // Season type is optional (UI no longer exposes it). If missing, preserve current DB value.
    $seasonType = strtoupper(trim($input['season_type'] ?? ''));
    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    
    if ($seasonId <= 0) {
        throw new Exception('ID mùa không hợp lệ');
    }
    
    // Validation
    if (empty($seasonName)) {
        throw new Exception('Tên mùa không được để trống');
    }
    
    // If season type is not provided, we will keep the existing type.
    
    if (empty($startDate)) {
        throw new Exception('Ngày bắt đầu không được để trống');
    }
    
    $db = ConnectionManager::getAccountDB();
    
    // Check if season exists and can be edited (only PENDING or ENDED)
    $checkStmt = $db->prepare("
        SELECT Status, SeasonName 
        FROM LuckyWheelSeasons 
        WHERE Id = ?
    ");
    $checkStmt->execute([$seasonId]);
    $season = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        throw new Exception('Mùa không tồn tại');
    }
    
    if ($season['Status'] === 'ACTIVE') {
        throw new Exception('Không thể chỉnh sửa mùa đang active');
    }

    // Load current type and preserve it if the request doesn't provide one.
    $typeStmt = $db->prepare("SELECT SeasonType FROM LuckyWheelSeasons WHERE Id = ?");
    $typeStmt->execute([$seasonId]);
    $typeRow = $typeStmt->fetch(PDO::FETCH_ASSOC);
    $currentSeasonType = strtoupper(trim($typeRow['SeasonType'] ?? 'DAY'));
    if (empty($seasonType) || !in_array($seasonType, ['WEEK', 'DAY'])) {
        $seasonType = $currentSeasonType;
    }
    
    // Check if season name already exists (excluding current season)
    $nameCheckStmt = $db->prepare("
        SELECT COUNT(*) as cnt 
        FROM LuckyWheelSeasons 
        WHERE SeasonName = ? AND Id != ?
    ");
    $nameCheckStmt->execute([$seasonName, $seasonId]);
    $exists = $nameCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (intval($exists['cnt']) > 0) {
        throw new Exception('Tên mùa đã tồn tại');
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

    // Validate no time-overlap with existing seasons (excluding current)
    $db = ConnectionManager::getAccountDB();

    $overlapStmt = $db->prepare("
        SELECT TOP 1 StartDate, EndDate, SeasonName
        FROM LuckyWheelSeasons
        WHERE Id != ?
          AND StartDate < ?
          AND EndDate > ?
        ORDER BY StartDate DESC
    ");
    $overlapStmt->execute([
        $seasonId,
        $endDateTime->format('Y-m-d H:i:s'),
        $startDateTime->format('Y-m-d H:i:s')
    ]);
    $overlap = $overlapStmt->fetch(PDO::FETCH_ASSOC);

    if ($overlap) {
        $suggestedStart = (new DateTime($overlap['EndDate']))->modify('+1 day');
        throw new Exception(
            'Khoảng thời gian đang trùng với mùa ' . ($overlap['SeasonName'] ?? '') .
            '. Vui lòng chọn thời gian bắt đầu sau ' . $suggestedStart->format('d/m/Y H:i')
        );
    }
    
    // Update season
    $updateStmt = $db->prepare("
        UPDATE LuckyWheelSeasons 
        SET SeasonName = ?,
            SeasonType = ?,
            StartDate = ?,
            EndDate = ?,
            UpdatedDate = GETDATE()
        WHERE Id = ?
    ");
    
    $updateStmt->execute([
        $seasonName,
        $seasonType,
        $startDateTime->format('Y-m-d H:i:s'),
        $endDateTime->format('Y-m-d H:i:s'),
        $seasonId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật mùa thành công'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
