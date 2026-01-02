<?php
/**
 * Payment Log Viewer API
 * GET /api/sepay/view_logs.php?order_code=ORDER123&limit=50
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../includes/payment_logger.php';

// Check if user is logged in (optional - can be public for admin)
$isAdmin = isset($_SESSION['user_id']) && ($_SESSION['is_admin'] ?? false);

// Get parameters
$orderCode = $_GET['order_code'] ?? null;
$limit = intval($_GET['limit'] ?? 50);
$level = $_GET['level'] ?? null; // DEBUG, INFO, WARNING, ERROR

try {
    if ($orderCode) {
        // Get logs for specific order
        $logs = PaymentLogger::getOrderLogs($orderCode, $limit);
        try {
            echo json_encode([
                'success' => true,
                'order_code' => $orderCode,
                'logs' => $logs,
                'count' => count($logs)
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("[ViewLogs] Error encoding response: " . $e->getMessage());
            http_response_code(500);
            echo '{"success":false,"error":"Internal server error"}';
        }
    } else {
        // Get recent logs
        $logs = PaymentLogger::getRecentLogs($limit, $level);
        try {
            echo json_encode([
                'success' => true,
                'logs' => $logs,
                'count' => count($logs),
                'filter' => [
                    'level' => $level,
                    'limit' => $limit
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("[ViewLogs] Error encoding response: " . $e->getMessage());
            http_response_code(500);
            echo '{"success":false,"error":"Internal server error"}';
        }
    }
} catch (Throwable $e) {
    error_log("[ViewLogs] API error: " . $e->getMessage());
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        error_log("[ViewLogs] Error encoding exception response: " . $encodeError->getMessage());
        echo '{"success":false,"error":"Internal server error"}';
    }
}

