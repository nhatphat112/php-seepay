<?php
/**
 * API: Lấy cấu hình tính năng nạp tích lũy
 * GET /api/tichnap/get_config.php
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "featureEnabled": true
 *   }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../connection_manager.php';

try {
    $db = ConnectionManager::getAccountDB();
    
    // Lấy config (luôn có 1 record)
    $stmt = $db->prepare("
        SELECT TOP 1 FeatureEnabled
        FROM TichNapConfig
        ORDER BY UpdatedDate DESC
    ");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nếu chưa có config, tạo mặc định
    if (!$config) {
        $stmt = $db->prepare("
            INSERT INTO TichNapConfig (FeatureEnabled, UpdatedDate)
            VALUES (1, GETDATE())
        ");
        $stmt->execute();
        $featureEnabled = true;
    } else {
        $featureEnabled = (bool)$config['FeatureEnabled'];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'featureEnabled' => $featureEnabled
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'data' => ['featureEnabled' => false]
    ], JSON_UNESCAPED_UNICODE);
}

