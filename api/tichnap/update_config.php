<?php
/**
 * API: Cập nhật cấu hình tính năng (Admin only)
 * POST /api/tichnap/update_config.php
 * 
 * Request:
 * {
 *   "featureEnabled": true,
 *   "eventStartDate": "2025-01-10T00:00:00",
 *   "eventEndDate": "2025-01-20T23:59:59"
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../connection_manager.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $featureEnabled   = isset($input['featureEnabled']) ? (bool)$input['featureEnabled'] : false;
    $eventStartDate   = $input['eventStartDate'] ?? null;
    $eventEndDate     = $input['eventEndDate'] ?? null;
    $adminJID = $_SESSION['user_id'] ?? null;
    
    // Convert datetime format từ "2026-01-31T22:05" sang format SQL Server
    // SQL Server cần format: "YYYY-MM-DD HH:MM:SS" hoặc "YYYY-MM-DDTHH:MM:SS"
    if (!empty($eventStartDate)) {
        // Nếu format là "YYYY-MM-DDTHH:MM" (thiếu giây), thêm ":00"
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $eventStartDate)) {
            $eventStartDate .= ':00';
        }
        // Convert sang format SQL Server: "YYYY-MM-DD HH:MM:SS"
        $eventStartDate = str_replace('T', ' ', $eventStartDate);
    } else {
        $eventStartDate = null;
    }
    
    if (!empty($eventEndDate)) {
        // Nếu format là "YYYY-MM-DDTHH:MM" (thiếu giây), thêm ":00"
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $eventEndDate)) {
            $eventEndDate .= ':00';
        }
        // Convert sang format SQL Server: "YYYY-MM-DD HH:MM:SS"
        $eventEndDate = str_replace('T', ' ', $eventEndDate);
    } else {
        $eventEndDate = null;
    }
    
    $db = ConnectionManager::getAccountDB();
    
    // Update config
    $stmt = $db->prepare("
        UPDATE TichNapConfig
        SET FeatureEnabled = ?, 
            EventStartDate = ?, 
            EventEndDate   = ?, 
            UpdatedDate    = GETDATE(), 
            UpdatedBy      = ?
        WHERE Id = (SELECT TOP 1 Id FROM TichNapConfig ORDER BY UpdatedDate DESC)
    ");
    $stmt->execute([
        $featureEnabled ? 1 : 0,
        $eventStartDate,
        $eventEndDate,
        $adminJID
    ]);
    
    // Nếu không có record nào, tạo mới
    if ($stmt->rowCount() == 0) {
        $stmt = $db->prepare("
            INSERT INTO TichNapConfig (FeatureEnabled, EventStartDate, EventEndDate, UpdatedDate, UpdatedBy)
            VALUES (?, ?, ?, GETDATE(), ?)
        ");
        $stmt->execute([
            $featureEnabled ? 1 : 0,
            $eventStartDate,
            $eventEndDate,
            $adminJID
        ]);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $featureEnabled ? 'Đã bật tính năng nạp tích lũy' : 'Đã tắt tính năng nạp tích lũy',
        'data' => [
            'featureEnabled' => $featureEnabled,
            'eventStartDate' => $eventStartDate,
            'eventEndDate'   => $eventEndDate
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Update config error: " . $e->getMessage());
}

