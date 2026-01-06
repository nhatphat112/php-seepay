<?php
/**
 * Payment Log Viewer - Web Interface
 * View payment logs in a user-friendly format
 */

session_start();
require_once __DIR__ . '/includes/payment_logger.php';
require_once __DIR__ . '/includes/auth_helper.php';

$orderCode = $_GET['order_code'] ?? null;
$limit = intval($_GET['limit'] ?? 100);
$level = $_GET['level'] ?? null;

$username = $_SESSION['username'] ?? 'Player';
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = getUserRole();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Logs Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #fff;
            padding: 0;
            margin: 0;
        }
        
        body.home-page {
            overflow: hidden;
        }
        
        /* Dashboard layout with sidebar */
        .dashboard-wrapper {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(15px);
            z-index: 9999;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Sidebar */
        .dashboard-sidebar {
            width: 260px;
            background: rgba(22, 33, 62, 0.95);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 2px solid #1e90ff;
            z-index: 10000;
            -webkit-overflow-scrolling: touch;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(30, 144, 255, 0.3);
            margin-bottom: 20px;
        }
        
        .sidebar-header h1 {
            font-size: 1.5rem;
            color: #ffd700;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header p {
            font-size: 0.85rem;
            color: #87ceeb;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-menu li {
            margin: 5px 0;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #87ceeb;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            background: rgba(30, 144, 255, 0.1);
            border-left-color: #1e90ff;
            color: #ffd700;
        }
        
        .nav-menu a i {
            margin-right: 10px;
            width: 20px;
            font-size: 18px;
        }
        
        /* Mobile menu toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 10001;
            background: rgba(30, 144, 255, 0.2);
            border: 1px solid #1e90ff;
            color: #87ceeb;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
        }
        
        .menu-toggle:hover {
            background: rgba(30, 144, 255, 0.3);
        }
        
        .container {
            flex: 1;
            margin-left: 260px;
            width: calc(100% - 260px);
            max-width: calc(100% - 260px);
            padding: 40px;
            position: relative;
            overflow-x: hidden;
            overflow-y: visible;
            min-height: 100vh;
            box-sizing: border-box;
            background: transparent;
            z-index: 1;
        }
        
        h1 {
            color: #e8c088;
            margin-bottom: 20px;
        }
        
        .filters {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #87ceeb;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #4682b4;
            border-radius: 5px;
            background: #0f1624;
            color: #fff;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 20px;
            background: #1e90ff;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #00bfff;
        }
        
        .logs-container {
            background: #16213e;
            border-radius: 10px;
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .log-entry {
            background: #0f1624;
            border-left: 4px solid #4682b4;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .log-entry.DEBUG {
            border-left-color: #87ceeb;
        }
        
        .log-entry.INFO {
            border-left-color: #1e90ff;
        }
        
        .log-entry.WARNING {
            border-left-color: #ffc107;
        }
        
        .log-entry.ERROR {
            border-left-color: #dc3545;
        }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #4682b4;
        }
        
        .log-level {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .log-level.DEBUG {
            background: #87ceeb;
            color: #000;
        }
        
        .log-level.INFO {
            background: #1e90ff;
            color: #fff;
        }
        
        .log-level.WARNING {
            background: #ffc107;
            color: #000;
        }
        
        .log-level.ERROR {
            background: #dc3545;
            color: #fff;
        }
        
        .log-timestamp {
            color: #87ceeb;
            font-size: 11px;
        }
        
        .log-action {
            color: #e8c088;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .log-data {
            background: #000;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            overflow-x: auto;
        }
        
        .log-data pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .log-meta {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 11px;
            color: #87ceeb;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #16213e;
            padding: 15px;
            border-radius: 10px;
            flex: 1;
        }
        
        .stat-box h3 {
            color: #87ceeb;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #e8c088;
        }
        
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .dashboard-wrapper {
                overflow-y: auto;
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
                position: fixed;
                width: 100%;
                height: 100%;
            }
            
            .dashboard-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .dashboard-sidebar.open {
                transform: translateX(0);
            }
            
            .container {
                margin-left: 0;
                max-width: 100%;
                padding: 60px 15px 30px;
                overflow-x: hidden;
                overflow-y: visible;
                min-height: auto;
            }
        }
    </style>
</head>
<body class="home-page">
    <!-- Dashboard Wrapper -->
    <div class="dashboard-wrapper">
        <!-- Menu Toggle for Mobile -->
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="dashboardSidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-user-circle"></i> Dashboard</h1>
                <p><?php echo htmlspecialchars($username); ?></p>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="<?php echo getNavActiveClass('dashboard.php'); ?>"><i class="fas fa-home"></i> Trang Chủ</a></li>
                <li><a href="transaction_history.php" class="<?php echo getNavActiveClass('transaction_history.php'); ?>"><i class="fas fa-history"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="payment.php" class="<?php echo getNavActiveClass('payment.php'); ?>"><i class="fas fa-credit-card"></i> Nạp Tiền</a></li>
                <li><a href="download.php" class="<?php echo getNavActiveClass('download.php'); ?>"><i class="fas fa-download"></i> Tải Game</a></li>
                <li><a href="ranking.php" class="<?php echo getNavActiveClass('ranking.php'); ?>"><i class="fas fa-trophy"></i> Xếp Hạng</a></li>
                <li><a href="view_payment_logs.php" class="<?php echo getNavActiveClass('view_payment_logs.php'); ?>"><i class="fas fa-file-alt"></i> Payment Logs</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin/cms/index.php" class="<?php echo getNavActiveClass('admin/cms/index.php'); ?>"><i class="fas fa-cog"></i> CMS Admin</a></li>
                <?php endif; ?>
                <li><a href="index.php"><i class="fas fa-globe"></i> Trang Chủ Website</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <div class="container">
        <h1>📋 Payment Logs Viewer</h1>
        
        <div class="filters">
            <div class="filter-group">
                <label>Order Code</label>
                <input type="text" id="orderCode" placeholder="ORDER123..." value="<?php echo htmlspecialchars($orderCode ?? ''); ?>">
            </div>
            <div class="filter-group">
                <label>Level</label>
                <select id="level">
                    <option value="">All</option>
                    <option value="DEBUG" <?php echo $level === 'DEBUG' ? 'selected' : ''; ?>>DEBUG</option>
                    <option value="INFO" <?php echo $level === 'INFO' ? 'selected' : ''; ?>>INFO</option>
                    <option value="WARNING" <?php echo $level === 'WARNING' ? 'selected' : ''; ?>>WARNING</option>
                    <option value="ERROR" <?php echo $level === 'ERROR' ? 'selected' : ''; ?>>ERROR</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Limit</label>
                <input type="number" id="limit" value="<?php echo $limit; ?>" min="1" max="1000">
            </div>
            <button class="btn" onclick="loadLogs()">🔍 Load Logs</button>
            <button class="btn" onclick="clearFilters()">🔄 Clear</button>
        </div>
        
        <div id="stats" class="stats"></div>
        
        <div class="logs-container" id="logsContainer">
            <div style="text-align: center; color: #87ceeb; padding: 20px;">
                Click "Load Logs" to view payment logs
            </div>
        </div>
    </div>
    
    <script>
        function loadLogs() {
            const orderCode = document.getElementById('orderCode').value.trim();
            const level = document.getElementById('level').value;
            const limit = document.getElementById('limit').value;
            
            let url = '/api/sepay/view_logs.php?limit=' + limit;
            if (orderCode) {
                url += '&order_code=' + encodeURIComponent(orderCode);
            }
            if (level) {
                url += '&level=' + encodeURIComponent(level);
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayLogs(data.logs);
                        displayStats(data.logs);
                    } else {
                        document.getElementById('logsContainer').innerHTML = 
                            '<div style="color: #dc3545; padding: 20px;">Error: ' + (data.error || 'Unknown error') + '</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('logsContainer').innerHTML = 
                        '<div style="color: #dc3545; padding: 20px;">Error loading logs: ' + error.message + '</div>';
                });
        }
        
        function displayStats(logs) {
            const stats = {
                total: logs.length,
                debug: logs.filter(l => l.level === 'DEBUG').length,
                info: logs.filter(l => l.level === 'INFO').length,
                warning: logs.filter(l => l.level === 'WARNING').length,
                error: logs.filter(l => l.level === 'ERROR').length
            };
            
            document.getElementById('stats').innerHTML = `
                <div class="stat-box">
                    <h3>Total Logs</h3>
                    <div class="value">${stats.total}</div>
                </div>
                <div class="stat-box">
                    <h3>INFO</h3>
                    <div class="value" style="color: #1e90ff;">${stats.info}</div>
                </div>
                <div class="stat-box">
                    <h3>WARNING</h3>
                    <div class="value" style="color: #ffc107;">${stats.warning}</div>
                </div>
                <div class="stat-box">
                    <h3>ERROR</h3>
                    <div class="value" style="color: #dc3545;">${stats.error}</div>
                </div>
            `;
        }
        
        function displayLogs(logs) {
            if (logs.length === 0) {
                document.getElementById('logsContainer').innerHTML = 
                    '<div style="text-align: center; color: #87ceeb; padding: 20px;">No logs found</div>';
                return;
            }
            
            const html = logs.map(log => {
                return `
                    <div class="log-entry ${log.level}">
                        <div class="log-header">
                            <div>
                                <span class="log-level ${log.level}">${log.level}</span>
                                <span class="log-action">${log.action}</span>
                            </div>
                            <div class="log-timestamp">${log.timestamp}</div>
                        </div>
                        <div class="log-meta">
                            ${log.order_code ? `<span>Order: <strong>${log.order_code}</strong></span>` : ''}
                            ${log.user_jid ? `<span>User JID: <strong>${log.user_jid}</strong></span>` : ''}
                            <span>IP: ${log.ip_address}</span>
                        </div>
                        ${log.data && Object.keys(log.data).length > 0 ? `
                            <div class="log-data">
                                <pre>${JSON.stringify(log.data, null, 2)}</pre>
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
            document.getElementById('logsContainer').innerHTML = html;
        }
        
        function clearFilters() {
            document.getElementById('orderCode').value = '';
            document.getElementById('level').value = '';
            document.getElementById('limit').value = 100;
            document.getElementById('logsContainer').innerHTML = 
                '<div style="text-align: center; color: #87ceeb; padding: 20px;">Click "Load Logs" to view payment logs</div>';
            document.getElementById('stats').innerHTML = '';
        }
        
        // Auto-load on page load if filters are set
        <?php if ($orderCode || $level): ?>
        window.addEventListener('DOMContentLoaded', loadLogs);
        <?php endif; ?>
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('dashboardSidebar');
            sidebar.classList.toggle('open');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('dashboardSidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (sidebar && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>

