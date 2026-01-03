<?php
/**
 * View Upload Logs
 * Xem logs chi tiết quá trình upload từ error_log
 */

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$logFile = ini_get('error_log');
if (empty($logFile)) {
    // Default error log locations
    $possibleLogs = [
        '/var/log/php_errors.log',
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
        sys_get_temp_dir() . '/php_errors.log'
    ];
    foreach ($possibleLogs as $file) {
        if (file_exists($file)) {
            $logFile = $file;
            break;
        }
    }
}

$logs = [];
if ($logFile && file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Newest first
    
    foreach ($lines as $line) {
        if (strpos($line, '[UPLOAD_LOG]') !== false) {
            $jsonPart = substr($line, strpos($line, '[UPLOAD_LOG]') + 12);
            $log = json_decode(trim($jsonPart), true);
            if ($log) {
                $logs[] = $log;
                if (count($logs) >= $limit) {
                    break;
                }
            }
        }
    }
} else {
    $errorMessage = "Không tìm thấy error log file. Kiểm tra php.ini error_log setting hoặc xem logs trong: " . implode(', ', $possibleLogs ?? []);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Logs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #1a1a2e;
            color: #fff;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #e8c088;
        }
        .log-entry {
            background: #16213e;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #4682b4;
        }
        .log-entry.error {
            border-left-color: #ff6b6b;
        }
        .log-entry.success {
            border-left-color: #4caf50;
        }
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .log-timestamp {
            color: #87ceeb;
            font-size: 0.9em;
        }
        .log-message {
            font-weight: bold;
            color: #e8c088;
            margin-bottom: 10px;
        }
        .log-section {
            margin: 10px 0;
            padding: 10px;
            background: #0f1624;
            border-radius: 3px;
        }
        .log-section h4 {
            color: #87ceeb;
            margin-top: 0;
            margin-bottom: 8px;
        }
        .log-data {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .error-text {
            color: #ff6b6b;
        }
        .success-text {
            color: #4caf50;
        }
        .warning-text {
            color: #ffa500;
        }
        .controls {
            margin-bottom: 20px;
            padding: 15px;
            background: #16213e;
            border-radius: 5px;
        }
        .controls input, .controls select {
            padding: 8px;
            background: #0f1624;
            border: 1px solid #4682b4;
            border-radius: 3px;
            color: #fff;
            margin-right: 10px;
        }
        .controls button {
            padding: 8px 15px;
            background: #4682b4;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .controls button:hover {
            background: #5a9bd4;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #16213e;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #e8c088;
        }
        .stat-label {
            color: #87ceeb;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Logs</h1>
        
        <div class="controls">
            <form method="GET" style="display: inline;">
                <label>Limit:</label>
                <select name="limit">
                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                </select>
                <button type="submit">Refresh</button>
            </form>
            <button onclick="location.reload()">Reload</button>
            <?php if ($logFile): ?>
                <span style="margin-left: 20px; color: #87ceeb;">
                    Log file: <?php echo htmlspecialchars($logFile); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (isset($errorMessage)): ?>
            <div class="log-entry error">
                <p class="error-text"><?php echo htmlspecialchars($errorMessage); ?></p>
                <p>Để xem logs, bạn có thể:</p>
                <ul>
                    <li>Kiểm tra file error_log trong php.ini: <code>php -i | grep error_log</code></li>
                    <li>Xem logs trực tiếp: <code>tail -f <?php echo htmlspecialchars($logFile ?: '/var/log/php_errors.log'); ?></code></li>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php
        $totalLogs = count($logs);
        $errorLogs = 0;
        $successLogs = 0;
        
        foreach ($logs as $log) {
            if (isset($log['data']['error'])) {
                $errorLogs++;
            } elseif (strpos($log['message'], 'successfully') !== false) {
                $successLogs++;
            }
        }
        ?>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-value"><?php echo $totalLogs; ?></div>
                <div class="stat-label">Total Logs</div>
            </div>
            <div class="stat-box">
                <div class="stat-value success-text"><?php echo $successLogs; ?></div>
                <div class="stat-label">Success</div>
            </div>
            <div class="stat-box">
                <div class="stat-value error-text"><?php echo $errorLogs; ?></div>
                <div class="stat-label">Errors</div>
            </div>
        </div>
        
        <?php if (empty($logs)): ?>
            <div class="log-entry">
                <p>No logs found. Try uploading a file first.</p>
            </div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <div class="log-entry <?php echo isset($log['data']['error']) ? 'error' : (strpos($log['message'], 'successfully') !== false ? 'success' : ''); ?>">
                    <div class="log-header">
                        <div class="log-timestamp"><?php echo htmlspecialchars($log['timestamp']); ?></div>
                    </div>
                    <div class="log-message"><?php echo htmlspecialchars($log['message']); ?></div>
                    
                    <?php if (isset($log['data']['error'])): ?>
                        <div class="log-section">
                            <h4 class="error-text">Error:</h4>
                            <div class="log-data error-text">
                                <?php 
                                if (is_array($log['data']['error'])) {
                                    echo htmlspecialchars(json_encode($log['data']['error'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                } else {
                                    echo htmlspecialchars($log['data']['error']);
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($log['files_data']['files_detail'])): ?>
                        <div class="log-section">
                            <h4>Files Data:</h4>
                            <div class="log-data">
                                <?php echo htmlspecialchars(json_encode($log['files_data']['files_detail'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="log-section">
                        <h4>PHP Config:</h4>
                        <div class="log-data">
                            <?php echo htmlspecialchars(json_encode($log['php_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($log['data']) && !isset($log['data']['error'])): ?>
                        <div class="log-section">
                            <h4>Additional Data:</h4>
                            <div class="log-data">
                                <?php echo htmlspecialchars(json_encode($log['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

