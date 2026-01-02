<?php
/**
 * Payment Logger Class
 * Tracks all payment-related activities for debugging and auditing
 * 
 * Features:
 * - File-based logging (structured JSON)
 * - Database logging (optional)
 * - Log levels: INFO, WARNING, ERROR, DEBUG
 * - Automatic log rotation
 */

class PaymentLogger {
    private static $logDir = null;
    private static $logFile = null;
    private static $dbLogging = false;
    
    // Log levels
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    
    /**
     * Initialize logger
     */
    public static function init() {
        // Set log directory
        self::$logDir = __DIR__ . '/../logs/payment';
        
        // Create log directory if not exists
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        // Set log file (daily rotation)
        $date = date('Y-m-d');
        self::$logFile = self::$logDir . '/payment_' . $date . '.log';
        
        // Enable database logging if configured
        self::$dbLogging = getenv('PAYMENT_LOG_DB') === 'true';
    }
    
    /**
     * Log payment activity
     * 
     * @param string $level Log level (DEBUG, INFO, WARNING, ERROR)
     * @param string $action Action name (e.g., 'create_order', 'webhook_received')
     * @param array $data Additional data to log
     * @param string $orderCode Order code (if applicable)
     * @param int $userJID User ID (if applicable)
     */
    public static function log($level, $action, $data = [], $orderCode = null, $userJID = null) {
        self::init();
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'level' => $level,
            'action' => $action,
            'order_code' => $orderCode,
            'user_jid' => $userJID,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data,
            'trace' => self::getTrace()
        ];
        
        // Log to file
        self::logToFile($logEntry);
        
        // Log to database (if enabled)
        if (self::$dbLogging) {
            self::logToDatabase($logEntry);
        }
        
        // Also log errors to PHP error_log
        if ($level === self::LEVEL_ERROR) {
            error_log("Payment Error [{$action}]: " . json_encode($data));
        }
    }
    
    /**
     * Log to file
     */
    private static function logToFile($logEntry) {
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log to database (optional)
     */
    private static function logToDatabase($logEntry) {
        try {
            require_once __DIR__ . '/../connection_manager.php';
            $db = ConnectionManager::getAccountDB();
            
            $stmt = $db->prepare("
                INSERT INTO TB_PaymentLog (
                    OrderCode, UserJID, Level, Action, Data, IPAddress, UserAgent, CreatedDate
                ) VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())
            ");
            
            $stmt->execute([
                $logEntry['order_code'],
                $logEntry['user_jid'],
                $logEntry['level'],
                $logEntry['action'],
                json_encode($logEntry['data'], JSON_UNESCAPED_UNICODE),
                $logEntry['ip_address'],
                $logEntry['user_agent']
            ]);
        } catch (Exception $e) {
            // Silently fail database logging to prevent breaking payment flow
            error_log("PaymentLogger database error: " . $e->getMessage());
        }
    }
    
    /**
     * Get stack trace (simplified)
     */
    private static function getTrace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $simplified = [];
        foreach ($trace as $frame) {
            if (isset($frame['file']) && isset($frame['line'])) {
                $simplified[] = [
                    'file' => basename($frame['file']),
                    'line' => $frame['line'],
                    'function' => $frame['function'] ?? ''
                ];
            }
        }
        return $simplified;
    }
    
    /**
     * Convenience methods
     */
    public static function debug($action, $data = [], $orderCode = null, $userJID = null) {
        self::log(self::LEVEL_DEBUG, $action, $data, $orderCode, $userJID);
    }
    
    public static function info($action, $data = [], $orderCode = null, $userJID = null) {
        self::log(self::LEVEL_INFO, $action, $data, $orderCode, $userJID);
    }
    
    public static function warning($action, $data = [], $orderCode = null, $userJID = null) {
        self::log(self::LEVEL_WARNING, $action, $data, $orderCode, $userJID);
    }
    
    public static function error($action, $data = [], $orderCode = null, $userJID = null) {
        self::log(self::LEVEL_ERROR, $action, $data, $orderCode, $userJID);
    }
    
    /**
     * Log order creation
     */
    public static function logOrderCreated($orderCode, $userJID, $amount, $paymentMethod, $sepayResponse = null) {
        self::info('order_created', [
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'sepay_response' => $sepayResponse
        ], $orderCode, $userJID);
    }
    
    /**
     * Log order status update
     */
    public static function logOrderStatusUpdate($orderCode, $oldStatus, $newStatus, $userJID = null, $reason = null) {
        self::info('order_status_update', [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason
        ], $orderCode, $userJID);
    }
    
    /**
     * Log webhook received
     */
    public static function logWebhookReceived($orderCode, $webhookData, $signature = null) {
        self::info('webhook_received', [
            'webhook_data' => $webhookData,
            'signature' => $signature ? 'present' : 'missing'
        ], $orderCode);
    }
    
    /**
     * Log webhook processed
     */
    public static function logWebhookProcessed($orderCode, $result, $userJID = null) {
        if ($result['success']) {
            self::info('webhook_processed', [
                'result' => $result
            ], $orderCode, $userJID);
        } else {
            self::error('webhook_processed_failed', [
                'error' => $result['error'] ?? 'Unknown error'
            ], $orderCode, $userJID);
        }
    }
    
    /**
     * Log API call
     */
    public static function logApiCall($endpoint, $method, $requestData, $responseData = null, $statusCode = null) {
        self::debug('api_call', [
            'endpoint' => $endpoint,
            'method' => $method,
            'request' => $requestData,
            'response' => $responseData,
            'status_code' => $statusCode
        ]);
    }
    
    /**
     * Log error
     */
    public static function logError($action, $error, $orderCode = null, $userJID = null) {
        self::error($action, [
            'error_message' => $error instanceof Exception ? $error->getMessage() : $error,
            'error_trace' => $error instanceof Exception ? $error->getTraceAsString() : null
        ], $orderCode, $userJID);
    }
    
    /**
     * Get logs for an order
     */
    public static function getOrderLogs($orderCode, $limit = 100) {
        self::init();
        
        $logs = [];
        $files = glob(self::$logDir . '/payment_*.log');
        rsort($files); // Newest first
        
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $log = json_decode($line, true);
                if ($log && isset($log['order_code']) && $log['order_code'] === $orderCode) {
                    $logs[] = $log;
                    if (count($logs) >= $limit) {
                        break 2;
                    }
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Get recent logs
     */
    public static function getRecentLogs($limit = 50, $level = null) {
        self::init();
        
        $logs = [];
        $files = glob(self::$logDir . '/payment_*.log');
        rsort($files);
        
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach (array_reverse($lines) as $line) {
                $log = json_decode($line, true);
                if ($log) {
                    if ($level === null || $log['level'] === $level) {
                        $logs[] = $log;
                        if (count($logs) >= $limit) {
                            break 2;
                        }
                    }
                }
            }
        }
        
        return array_reverse($logs);
    }
}

