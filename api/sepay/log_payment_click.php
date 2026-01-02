<?php
/**
 * Payment Click Logging API
 * POST /api/sepay/log_payment_click.php
 * 
 * This endpoint logs payment button clicks for analytics and debugging
 */

header('Content-Type: application/json; charset=utf-8');

// Allow CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use POST.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo '{"success":false,"error":"Internal server error"}';
    }
    exit;
}

try {
    // Get JSON data from request body
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (empty($data)) {
        http_response_code(400);
        try {
            echo json_encode([
                'success' => false,
                'error' => 'Empty request data'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo '{"success":false,"error":"Internal server error"}';
        }
        exit;
    }
    
    http_response_code(200);
    try {
        echo json_encode([
            'success' => true,
            'message' => 'Payment click logged successfully',
            'logged_at' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo '{"success":false,"error":"Internal server error"}';
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        echo '{"success":false,"error":"Internal server error"}';
    }
}

