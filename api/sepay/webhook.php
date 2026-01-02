<?php
/**
 * Sepay Webhook Handler
 * POST /api/sepay/webhook.php
 * 
 * This endpoint receives webhooks from Sepay when payment status changes.
 * 
 * Headers:
 * - X-Sepay-Signature: Webhook signature for verification
 * 
 * Request Body:
 * {
 *   "order_code": "ORDER12345678901234",
 *   "transaction_id": "TXN123456",
 *   "status": "completed",
 *   "amount": 100000,
 *   "message": "Payment successful"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Webhook processed successfully"
 * }
 */

// Disable ALL output buffering from the start
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Set headers first (same as get_order_status.php)
header('Content-Type: application/json; charset=utf-8');
header('Connection: close'); // Force close connection after response (important for tunnels)

// Set timeout and ignore user abort (for tunnel compatibility)
set_time_limit(30); // Max 30 seconds
ignore_user_abort(true); // Continue processing even if client disconnects

// Initialize result variable
$result = null;
$data = null;
$rawInput = '';

// Main execution wrapped in try-catch
try {
    // Load required files
    require_once __DIR__ . '/../../includes/sepay_service.php';

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $result = [
            'success' => false,
            'error' => 'Method not allowed. Use POST method.'
        ];
    }

    // Get raw input
    if ($result === null) {
        try {
            $rawInput = file_get_contents('php://input');
        } catch (Throwable $e) {
            $result = [
                'success' => false,
                'error' => 'Failed to read request data: ' . $e->getMessage()
            ];
        }
    }

    // Parse JSON data
    if ($result === null) {
        try {
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
                $data = $_POST;
            }
            
            if (empty($data)) {
                $result = [
                    'success' => false,
                    'error' => 'Empty webhook data - no data received'
                ];
            }
        } catch (Throwable $e) {
            $result = [
                'success' => false,
                'error' => 'Failed to parse webhook data: ' . $e->getMessage()
            ];
        }
    }
    
    // ============================================================================
    // CRITICAL: Send response IMMEDIATELY (before processing webhook)
    // This is essential for tunnels/proxies like DevTunnels that wait for script to finish
    // ============================================================================
    
    // Ensure result is set for response
    if ($result === null) {
        // If we have valid data, acknowledge receipt (will process later)
        if ($data !== null && !empty($data)) {
            $result = [
                'success' => true,
                'message' => 'Webhook received'
            ];
        } else {
            $result = [
                'success' => false,
                'error' => 'Unknown error - no data received'
            ];
        }
    }
    
    // ============================================================================
    // CRITICAL: Send response IMMEDIATELY with proper headers
    // ============================================================================
    http_response_code(200);
    
    try {
        $responseJson = json_encode($result, JSON_UNESCAPED_UNICODE);
        $responseLength = strlen($responseJson);
        
        // Set Content-Length header (important for tunnels)
        header('Content-Length: ' . $responseLength);
        
        // Disable output buffering completely
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Send response
        echo $responseJson;
        
        // CRITICAL: Flush ALL output buffers immediately
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request(); // PHP-FPM: client disconnected
        } else {
            // For non-FPM: ensure all buffers are flushed
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
            // Force flush again
            if (function_exists('ob_flush')) {
                ob_flush();
            }
            flush();
        }
        
    } catch (Throwable $e) {
        $errorResponse = '{"success":false,"error":"Internal server error"}';
        header('Content-Length: ' . strlen($errorResponse));
        echo $errorResponse;
        flush();
    }
    
    // Process webhook IMMEDIATELY (after response sent and flushed)
    if ($result['success'] && $data !== null && !empty($data)) {
        try {
            SepayService::processWebhook($data, $rawInput);
        } catch (Throwable $e) {
            // Silent fail - response already sent
        }
    }
    
    // Exit after processing (response already sent and flushed)
    exit(0);
    
} catch (Throwable $e) {
    // Catch any unhandled exceptions - still return 200 immediately
    http_response_code(200);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        echo '{"success":false,"error":"Internal server error"}';
    }
    
    // Flush and exit immediately
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
    exit(0);
}

