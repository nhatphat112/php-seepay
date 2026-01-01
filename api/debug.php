<?php
/**
 * Debug API - Shows detailed error information
 * DELETE THIS FILE IN PRODUCTION!
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Debug Info</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1f3a; color: #fff; }
        h2 { color: #d4a574; border-bottom: 2px solid #d4a574; padding-bottom: 10px; }
        .success { color: #48bb78; }
        .error { color: #f56565; }
        .info { background: rgba(255,255,255,0.05); padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #0a0e27; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { color: #d4a574; }
    </style>
</head>
<body>
    <h1>🐛 API Debug Information</h1>
    
    <h2>1. PHP Configuration</h2>
    <table>
        <tr><th>Setting</th><th>Value</th></tr>
        <tr><td>PHP Version</td><td><?php echo phpversion(); ?></td></tr>
        <tr><td>Display Errors</td><td><?php echo ini_get('display_errors') ? 'On' : 'Off'; ?></td></tr>
        <tr><td>Error Reporting</td><td><?php echo error_reporting(); ?></td></tr>
        <tr><td>Server Software</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
    </table>
    
    <h2>2. PHP Extensions</h2>
    <table>
        <tr><th>Extension</th><th>Status</th></tr>
        <?php
        $required = ['pdo', 'pdo_sqlsrv', 'sqlsrv', 'mbstring', 'json', 'session'];
        foreach ($required as $ext) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Not Loaded</span>';
            echo "<tr><td>$ext</td><td>$status</td></tr>";
        }
        ?>
    </table>
    
    <h2>3. Connection Manager Test</h2>
    <div class="info">
    <?php
    try {
        echo "Loading connection_manager.php...<br>";
        require_once '../connection_manager.php';
        echo '<span class="success">✓ Connection manager loaded successfully</span><br><br>';
        
        echo "Getting connection status...<br>";
        $status = ConnectionManager::getConnectionStatus();
        echo '<span class="success">✓ Connection status retrieved</span><br>';
        echo '<pre>' . print_r($status, true) . '</pre>';
        
    } catch (Exception $e) {
        echo '<span class="error">✗ Error: ' . $e->getMessage() . '</span><br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    ?>
    </div>
    
    <h2>4. Database Connection Test</h2>
    <div class="info">
    <?php
    try {
        echo "Testing ACCOUNT database...<br>";
        $accountDb = ConnectionManager::getAccountDB();
        $stmt = $accountDb->query("SELECT COUNT(*) as count FROM TB_User");
        $result = $stmt->fetch();
        echo '<span class="success">✓ ACCOUNT DB: ' . $result['count'] . ' users</span><br><br>';
        
        echo "Testing SHARD database...<br>";
        $shardDb = ConnectionManager::getShardDB();
        $stmt = $shardDb->query("SELECT COUNT(*) as count FROM _Char");
        $result = $stmt->fetch();
        echo '<span class="success">✓ SHARD DB: ' . $result['count'] . ' characters</span><br><br>';
        
        echo "Testing LOG database...<br>";
        $logDb = ConnectionManager::getLogDB();
        $stmt = $logDb->query("SELECT @@VERSION as version");
        $result = $stmt->fetch();
        echo '<span class="success">✓ LOG DB: Connected</span><br>';
        
    } catch (Exception $e) {
        echo '<span class="error">✗ Database Error: ' . $e->getMessage() . '</span><br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    ?>
    </div>
    
    <h2>5. API Endpoints Test</h2>
    <div class="info">
    <?php
    $apiTests = [
        'test.php' => 'Basic API Test',
        'server_status.php' => 'Server Status',
        'ranking.php?type=level' => 'Ranking - Level',
        'ranking.php?type=guild' => 'Ranking - Guild',
        'ranking.php?type=pvp' => 'Ranking - PvP'
    ];
    
    foreach ($apiTests as $endpoint => $name) {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $endpoint;
        echo "<strong>$name:</strong> <a href='$url' target='_blank' style='color: #d4a574;'>$url</a><br>";
    }
    ?>
    </div>
    
    <h2>6. Request Information</h2>
    <table>
        <tr><th>Variable</th><th>Value</th></tr>
        <tr><td>REQUEST_URI</td><td><?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?></td></tr>
        <tr><td>REQUEST_METHOD</td><td><?php echo $_SERVER['REQUEST_METHOD'] ?? 'N/A'; ?></td></tr>
        <tr><td>HTTP_HOST</td><td><?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?></td></tr>
        <tr><td>REMOTE_ADDR</td><td><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></td></tr>
        <tr><td>HTTP_REFERER</td><td><?php echo $_SERVER['HTTP_REFERER'] ?? 'N/A'; ?></td></tr>
    </table>
    
    <h2>7. Error Log (Last 20 lines)</h2>
    <div class="info">
    <?php
    $errorLog = ini_get('error_log');
    if (empty($errorLog)) {
        // Try common locations
        $possibleLogs = [
            'C:/xampp/apache/logs/error.log',
            'C:/xampp/php/logs/php_error_log',
            '/var/log/apache2/error.log',
            '/var/log/php_errors.log'
        ];
        foreach ($possibleLogs as $log) {
            if (file_exists($log)) {
                $errorLog = $log;
                break;
            }
        }
    }
    
    if ($errorLog && file_exists($errorLog)) {
        echo "<strong>Log file:</strong> $errorLog<br><br>";
        $lines = file($errorLog);
        $lastLines = array_slice($lines, -20);
        echo '<pre>' . htmlspecialchars(implode('', $lastLines)) . '</pre>';
    } else {
        echo '<span class="error">Error log not found or not configured</span>';
    }
    ?>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: rgba(237, 137, 54, 0.2); border-radius: 5px;">
        <strong>⚠️ WARNING:</strong> Delete this file (api/debug.php) after debugging! 
        It exposes sensitive information.
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="../index.php" style="color: #d4a574;">← Back to Homepage</a>
    </div>
</body>
</html>

