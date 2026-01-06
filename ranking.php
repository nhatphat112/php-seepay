<?php
session_start();
require_once 'includes/auth_helper.php';

$username = $_SESSION['username'] ?? 'Player';
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = getUserRole();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Xếp Hạng - Song Long Tranh Bá Mobile</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico"/>
    
    <!-- CSS - Sử dụng giống trang chính -->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/vendor.css" />
    <link rel="stylesheet" href="assets/css/main1bce.css?v=6" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/font-override.css" />
    <link rel="stylesheet" href="css/auth-enhanced.css" />
    
    <style>
        /* Fix scroll - allow scrolling when content is longer */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
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
        
        /* Main content */
        .ranking-container {
            flex: 1;
            margin-left: 260px;
            width: calc(100% - 260px);
            max-width: calc(100% - 260px);
            background: rgba(20, 20, 30, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            position: relative;
            overflow-x: hidden;
            overflow-y: visible;
            min-height: 100vh;
            border-radius: 0;
            box-sizing: border-box;
            box-shadow: none;
            margin: 0;
            border: none !important;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .ranking-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #999;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #ffd700;
        }
        
        .ranking-header h1 {
            font-size: 28px;
            color: #ffd700;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .ranking-header p {
            color: #999;
            font-size: 15px;
        }
        
        /* Tabs */
        .ranking-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .tab-btn {
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 215, 0, 0.4);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            border-color: #ffd700;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
        
        .tab-btn i {
            font-size: 16px;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .loading i {
            font-size: 48px;
            color: #ffd700;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Ranking Table */
        .ranking-table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .ranking-table thead {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 237, 78, 0.2) 100%);
        }
        
        .ranking-table th {
            padding: 15px;
            text-align: left;
            color: #ffd700;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
        }
        
        .ranking-table th:first-child {
            text-align: center;
            width: 80px;
        }
        
        .ranking-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }
        
        .ranking-table tbody tr:hover {
            background: rgba(255, 215, 0, 0.1);
        }
        
        .ranking-table td {
            padding: 15px;
            color: #fff;
            font-size: 14px;
        }
        
        .rank-cell {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .rank-1 {
            color: #ffd700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        
        .rank-2 {
            color: #c0c0c0;
        }
        
        .rank-3 {
            color: #cd7f32;
        }
        
        .rank-medal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.2);
            border: 2px solid currentColor;
        }
        
        .char-name {
            font-weight: 600;
            color: #ffd700;
        }
        
        .guild-name {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }
        
        .stat-value {
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .no-data i {
            font-size: 48px;
            color: #444;
            margin-bottom: 15px;
        }
        
        .back-home {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-home a:hover {
            color: #ffd700;
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
            
            .ranking-container {
                margin-left: 0;
                max-width: 100%;
                padding: 60px 15px 30px;
                overflow-x: hidden;
                overflow-y: visible;
                min-height: auto;
            }
            
            .ranking-tabs {
                gap: 5px;
            }
            
            .tab-btn {
                padding: 10px 15px;
                font-size: 12px;
            }
            
            .ranking-table th,
            .ranking-table td {
                padding: 10px 8px;
                font-size: 12px;
            }
        }
    </style>
    
    <!-- jQuery -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Trang Chủ</a></li>
                <li><a href="transaction_history.php"><i class="fas fa-history"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="payment.php"><i class="fas fa-credit-card"></i> Nạp Tiền</a></li>
                <li><a href="download.php"><i class="fas fa-download"></i> Tải Game</a></li>
                <li><a href="ranking.php" class="active"><i class="fas fa-trophy"></i> Xếp Hạng</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin/cms/index.php"><i class="fas fa-cog"></i> CMS Admin</a></li>
                <?php endif; ?>
                <li><a href="index.php"><i class="fas fa-globe"></i> Trang Chủ Website</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <div class="ranking-container">
            <!-- Header -->
            <div class="ranking-header">
                <h1 class="f-utm_nyala t-upper">
                    <i class="fas fa-trophy"></i> Bảng Xếp Hạng
                </h1>
                <p class="f-calibri">Top 25 nhân vật hàng đầu server</p>
            </div>

            <!-- Tabs -->
            <div class="ranking-tabs">
                <button class="tab-button active" data-type="level">
                    <i class="fas fa-star"></i> Cấp Độ
                </button>
                <button class="tab-button" data-type="ch">
                    <i class="fas fa-yin-yang"></i> Chinese
                </button>
                <button class="tab-button" data-type="eu">
                    <i class="fas fa-chess-knight"></i> European
                </button>
                <button class="tab-button" data-type="trader">
                    <i class="fas fa-store"></i> Thương Nhân
                </button>
                <button class="tab-button" data-type="hunter">
                    <i class="fas fa-crosshairs"></i> Thợ Săn
                </button>
                <button class="tab-button" data-type="thief">
                    <i class="fas fa-mask"></i> Kẻ Trộm
                </button>
                <button class="tab-button" data-type="gold">
                    <i class="fas fa-coins"></i> Giàu Nhất
                </button>
            </div>

            <!-- Content -->
            <div id="ranking-content">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải dữ liệu...</p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-home"></i> Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentType = 'level';
        
        // Load ranking data
        function loadRanking(type) {
            currentType = type;
            
            // Update active tab
            $('.tab-button').removeClass('active');
            $(`.tab-button[data-type="${type}"]`).addClass('active');
            
            // Show loading
            $('#ranking-content').html(`
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải dữ liệu...</p>
                </div>
            `);
            
            // Fetch data
            $.ajax({
                url: 'api/ranking_data.php',
                method: 'GET',
                data: { type: type },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        renderRankingTable(response.data, type);
                    } else {
                        $('#ranking-content').html(`
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>Chưa có dữ liệu xếp hạng</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    $('#ranking-content').html(`
                        <div class="no-data">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Lỗi khi tải dữ liệu: ${error}</p>
                        </div>
                    `);
                }
            });
        }
        
        // Render ranking table
        function renderRankingTable(data, type) {
            let html = '<div class="ranking-table-container"><table class="ranking-table">';
            
            // Header
            html += '<thead><tr>';
            html += '<th>Hạng</th>';
            html += '<th>Tên Nhân Vật</th>';
            
            switch(type) {
                case 'trader':
                case 'hunter':
                case 'thief':
                    html += '<th>Cấp Job</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Biệt Danh</th>';
                    break;
                case 'gold':
                    html += '<th>Cấp Độ</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Vàng</th>';
                    break;
                case 'ch':
                case 'eu':
                    html += '<th>Cấp Độ</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Điểm KN</th>';
                    html += '<th>Sức Mạnh</th>';
                    html += '<th>Trí Tuệ</th>';
                    break;
                default: // level
                    html += '<th>Cấp Độ</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Vàng</th>';
                    break;
            }
            
            html += '</tr></thead><tbody>';
            
            // Rows
            data.forEach((row, index) => {
                const rank = index + 1;
                let rankClass = '';
                let rankDisplay = rank;
                
                if (rank === 1) {
                    rankClass = 'rank-1';
                    rankDisplay = '<span class="rank-medal">🥇</span>';
                } else if (rank === 2) {
                    rankClass = 'rank-2';
                    rankDisplay = '<span class="rank-medal">🥈</span>';
                } else if (rank === 3) {
                    rankClass = 'rank-3';
                    rankDisplay = '<span class="rank-medal">🥉</span>';
                }
                
                html += `<tr>`;
                html += `<td class="rank-cell ${rankClass}">${rankDisplay}</td>`;
                html += `<td><span class="char-name">${escapeHtml(row.CharName16)}</span></td>`;
                
                switch(type) {
                    case 'trader':
                    case 'hunter':
                    case 'thief':
                        html += `<td class="stat-value">${row.Level || 0}</td>`;
                        html += `<td>${formatNumber(row.Exp || 0)}</td>`;
                        html += `<td><span class="guild-name">${escapeHtml(row.NickName16 || '-')}</span></td>`;
                        break;
                    case 'gold':
                        html += `<td class="stat-value">${row.CurLevel || 0}</td>`;
                        html += `<td>${formatNumber(row.ExpOffset || 0)}</td>`;
                        html += `<td class="stat-value">${formatNumber(row.RemainGold || 0)}</td>`;
                        break;
                    case 'ch':
                    case 'eu':
                        html += `<td class="stat-value">${row.CurLevel || 0}</td>`;
                        html += `<td>${formatNumber(row.ExpOffset || 0)}</td>`;
                        html += `<td>${row.RemainSkillPoint || 0}</td>`;
                        html += `<td>${row.Strength || 0}</td>`;
                        html += `<td>${row.Intellect || 0}</td>`;
                        break;
                    default: // level
                        html += `<td class="stat-value">${row.CurLevel || 0}</td>`;
                        html += `<td>${formatNumber(row.ExpOffset || 0)}</td>`;
                        html += `<td>${formatNumber(row.RemainGold || 0)}</td>`;
                        break;
                }
                
                html += `</tr>`;
            });
            
            html += '</tbody></table></div>';
            $('#ranking-content').html(html);
        }
        
        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num);
        }
        
        // Tab click handlers
        $('.tab-button').on('click', function() {
            const type = $(this).data('type');
            loadRanking(type);
        });
        
        // Load default ranking on page load
        $(document).ready(function() {
            loadRanking('level');
        });
        
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

