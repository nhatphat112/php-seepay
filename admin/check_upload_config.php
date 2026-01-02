<?php
/**
 * Check Upload Configuration
 * Kiểm tra cấu hình upload của PHP
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Upload Config</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #1a1a2e;
            color: #fff;
        }
        .config-item {
            background: #16213e;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #e8c088;
        }
        .config-item strong {
            color: #e8c088;
        }
        .warning {
            color: #ffa500;
        }
        .error {
            color: #ff6b6b;
        }
        .success {
            color: #4caf50;
        }
    </style>
</head>
<body>
    <h1>PHP Upload Configuration</h1>
    
    <div class="config-item">
        <strong>PHP SAPI:</strong> 
        <span class="<?php echo (in_array(php_sapi_name(), ['fpm-fcgi', 'cgi-fcgi'])) ? 'warning' : 'success'; ?>">
            <?php echo php_sapi_name(); ?>
        </span>
        <?php if (in_array(php_sapi_name(), ['fpm-fcgi', 'cgi-fcgi'])): ?>
            <span class="warning">⚠ PHP-FPM/CGI - .htaccess php_value will NOT work!</span>
        <?php elseif (in_array(php_sapi_name(), ['apache2handler', 'apache'])): ?>
            <span class="success">✓ mod_php - .htaccess should work</span>
        <?php endif; ?>
    </div>
    
    <div class="config-item">
        <strong>PHP Version:</strong> <?php echo phpversion(); ?>
    </div>
    
    <div class="config-item">
        <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?>
    </div>
    
    <div class="config-item">
        <strong>Loaded php.ini:</strong> <?php echo php_ini_loaded_file() ?: 'N/A'; ?>
    </div>
    
    <div class="config-item">
        <strong>upload_max_filesize:</strong> 
        <span class="<?php echo (ini_get('upload_max_filesize') < '10M') ? 'error' : 'success'; ?>">
            <?php echo ini_get('upload_max_filesize'); ?>
        </span>
        <?php if (ini_get('upload_max_filesize') < '10M'): ?>
            <span class="warning">⚠ Cần tăng lên ít nhất 10M</span>
        <?php endif; ?>
    </div>
    
    <div class="config-item">
        <strong>post_max_size:</strong> 
        <span class="<?php echo (ini_get('post_max_size') < '10M') ? 'error' : 'success'; ?>">
            <?php echo ini_get('post_max_size'); ?>
        </span>
        <?php if (ini_get('post_max_size') < '10M'): ?>
            <span class="warning">⚠ Cần tăng lên ít nhất 10M</span>
        <?php endif; ?>
    </div>
    
    <div class="config-item">
        <strong>max_execution_time:</strong> <?php echo ini_get('max_execution_time'); ?>s
    </div>
    
    <div class="config-item">
        <strong>max_input_time:</strong> <?php echo ini_get('max_input_time'); ?>s
    </div>
    
    <div class="config-item">
        <strong>memory_limit:</strong> <?php echo ini_get('memory_limit'); ?>
    </div>
    
    <div class="config-item">
        <strong>File Uploads Enabled:</strong> 
        <span class="<?php echo ini_get('file_uploads') ? 'success' : 'error'; ?>">
            <?php echo ini_get('file_uploads') ? 'Yes' : 'No'; ?>
        </span>
    </div>
    
    <div class="config-item">
        <strong>Upload Temp Directory:</strong> <?php echo ini_get('upload_tmp_dir') ?: 'System default'; ?>
    </div>
    
    <h2>Recommendations:</h2>
    <ul>
        <li>upload_max_filesize should be at least <strong>10M</strong></li>
        <li>post_max_size should be at least <strong>10M</strong> (should be >= upload_max_filesize)</li>
        <li>max_execution_time should be at least <strong>300</strong> seconds</li>
    </ul>
    
    <h2>How to Fix:</h2>
    <?php 
    $sapi = php_sapi_name();
    $isFPM = in_array($sapi, ['fpm-fcgi', 'cgi-fcgi']);
    $isModPHP = in_array($sapi, ['apache2handler', 'apache']);
    ?>
    
    <?php if ($isFPM): ?>
        <div class="config-item" style="background: #5a2d2d; border-left-color: #ff6b6b;">
            <strong>⚠️ PHP-FPM/CGI Detected</strong>
            <p>.htaccess php_value directives will NOT work with PHP-FPM/CGI!</p>
            <p>You need to set these values in:</p>
            <ol>
                <li><strong>php.ini</strong> file (recommended):
                    <pre style="background: #0f1624; padding: 10px; border-radius: 5px; margin: 10px 0;">
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
max_input_time = 300</pre>
                </li>
                <li><strong>PHP-FPM pool configuration</strong> (if using PHP-FPM):
                    <pre style="background: #0f1624; padding: 10px; border-radius: 5px; margin: 10px 0;">
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 10M</pre>
                </li>
            </ol>
        </div>
    <?php elseif ($isModPHP): ?>
        <div class="config-item" style="background: #2d5a3d; border-left-color: #4caf50;">
            <strong>✓ mod_php Detected</strong>
            <p>.htaccess php_value directives should work.</p>
            <p>If settings are not applied, check:</p>
            <ol>
                <li>Apache module <code>mod_php7.c</code> or <code>mod_php.c</code> is loaded</li>
                <li>.htaccess file is being read (check Apache AllowOverride directive)</li>
                <li>Restart Apache after changing .htaccess</li>
            </ol>
        </div>
    <?php else: ?>
        <div class="config-item" style="background: #5a4d2d; border-left-color: #ffa500;">
            <strong>⚠️ Unknown PHP SAPI: <?php echo htmlspecialchars($sapi); ?></strong>
            <p>Please check your PHP configuration manually.</p>
        </div>
    <?php endif; ?>
    
    <div class="config-item">
        <strong>General Steps:</strong>
        <ol>
            <li>Check <code>.htaccess</code> file (already configured for mod_php)</li>
            <li>If using PHP-FPM/CGI, update <code>php.ini</code> or PHP-FPM pool config</li>
            <li>Restart web server (Apache/Nginx) and PHP-FPM (if applicable)</li>
            <li>Contact hosting provider if you don't have access to php.ini</li>
        </ol>
    </div>
</body>
</html>

