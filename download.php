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
    <title>Tải Game - Song Long Tranh Bá Mobile</title>
    
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
        .download-box {
            flex: 1;
            margin-left: 260px;
            width: calc(100% - 260px);
            max-width: calc(100% - 260px);
            background: rgba(10, 20, 40, 0.95) !important;
            backdrop-filter: blur(20px);
            padding: 40px;
            position: relative;
            overflow-x: hidden;
            overflow-y: visible;
            min-height: 100vh;
            border: none;
            border-radius: 0;
            box-shadow: none;
            margin: 0;
            box-sizing: border-box;
            z-index: 1;
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
        
        .download-header {
            text-align: center;
            margin-bottom: 35px;
            padding-top: 20px; /* Thêm padding để tránh bị cắt */
            position: relative;
            z-index: 10;
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
        
        .download-logo {
            margin-bottom: 20px;
        }
        
        .logo-img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .download-logo h1 {
            font-size: 24px;
            color: #ffd700;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .download-header h2 {
            font-size: 26px;
            color: #fff;
            margin: 15px 0 10px;
        }
        
        .download-header p {
            color: #999;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .platform-section {
            margin-bottom: 30px;
        }
        
        .platform-title {
            font-size: 18px;
            color: #ffd700;
            margin-bottom: 20px;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .platform-title i {
            font-size: 20px;
        }
        
        .download-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .download-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .download-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 215, 0, 0.4);
            transform: translateX(5px);
        }
        
        .download-item-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .download-item-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            color: #ffd700;
            font-size: 20px;
        }
        
        .download-item-text {
            flex: 1;
        }
        
        .download-item-text .name {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 3px;
        }
        
        .download-item-text .desc {
            display: block;
            font-size: 12px;
            color: #888;
        }
        
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
        
        .download-btn i {
            font-size: 14px;
        }
        
        .system-requirements {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }
        
        .system-requirements h3 {
            font-size: 16px;
            color: #ffd700;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }
        
        .requirement-item {
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            text-align: center;
        }
        
        .requirement-item .label {
            display: block;
            font-size: 11px;
            color: #888;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .requirement-item .value {
            display: block;
            font-size: 14px;
            color: #fff;
            font-weight: 600;
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
            
            .download-box {
                margin-left: 0;
                max-width: 100%;
                padding: 60px 15px 30px;
                overflow-x: hidden;
                overflow-y: visible;
                min-height: auto;
            }
            
            .download-item {
                flex-direction: column;
                text-align: center;
            }
            
            .download-item-info {
                flex-direction: column;
                text-align: center;
            }
            
            .download-btn {
                width: 100%;
                justify-content: center;
            }
            
            .requirements-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <li><a href="dashboard.php" class="<?php echo getNavActiveClass('dashboard.php'); ?>"><i class="fas fa-home"></i> Trang Chủ</a></li>
                <li><a href="transaction_history.php" class="<?php echo getNavActiveClass('transaction_history.php'); ?>"><i class="fas fa-history"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="payment.php" class="<?php echo getNavActiveClass('payment.php'); ?>"><i class="fas fa-credit-card"></i> Nạp Tiền</a></li>
                <li><a href="download.php" class="<?php echo getNavActiveClass('download.php'); ?>"><i class="fas fa-download"></i> Tải Game</a></li>
                <li><a href="ranking.php" class="<?php echo getNavActiveClass('ranking.php'); ?>"><i class="fas fa-trophy"></i> Xếp Hạng</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin/cms/index.php" class="<?php echo getNavActiveClass('admin/cms/index.php'); ?>"><i class="fas fa-cog"></i> CMS Admin</a></li>
                <?php endif; ?>
                <li><a href="index.php"><i class="fas fa-globe"></i> Trang Chủ Website</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <div class="download-box">
            <div class="download-header">
                <div class="download-logo">
                    <img src="assets/images/logo.png" alt="Logo" class="logo-img">
                    <h1 class="f-utm_nyala t-upper">Song Long Tranh Bá</h1>
                </div>
                <h2 class="f-cambria">Tải Game Ngay</h2>
                <p class="f-calibri">Chọn phiên bản phù hợp và bắt đầu hành trình huyền thoại</p>
            </div>

            <!-- Game Client Downloads -->
            <div class="platform-section">
                <h3 class="platform-title">
                    <i class="fas fa-download"></i> Tải Client Game (PC)
                </h3>
                <div class="download-list">
                    <div class="download-item">
                        <div class="download-item-info">
                            <div class="download-item-icon">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div class="download-item-text">
                                <span class="name">Sao chép liên kết</span>
                                <span class="desc">https://drive.google.com/file/d/147t-q3iexbLszFjeeW9AKEW-d9VQ7rHu/view?usp=sharing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="new-home" id="downloads" style="padding: 60px 20px;">
                <div class="title-frame t-center t-upper d-flex a-center j-center">
                    <img src="assets/images/title/img-title3860.png" alt="">
                    <div class="name-title vi">Tải Game Client</div>
                    <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
                </div>
                
                <div class="download-section t-center" style="max-width: 800px; margin: 40px auto; padding: 40px; background: rgba(255, 255, 255, 0.03); border-radius: 15px;">
                    <div style="margin-bottom: 30px;">
                        <h3 style="color: #ffd700; font-size: 24px; margin-bottom: 15px; font-weight: 600;">Tải Game Client</h3>
                        <p style="color: #999; font-size: 16px; margin-bottom: 30px;">Tải xuống phiên bản mới nhất của game để bắt đầu hành trình của bạn</p>
                    </div>
                    
                    <a href="https://drive.google.com/file/d/147t-q3iexbLszFjeeW9AKEW-d9VQ7rHu/view?usp=sharing" 
                       target="_blank" 
                       class="download-btn" 
                       style="display: inline-flex; align-items: center; gap: 12px; padding: 18px 40px; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #000; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 18px; text-transform: uppercase; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);">
                        <i class="fas fa-download" style="font-size: 20px;"></i>
                        <span>Tải Game Client</span>
                    </a>
                    
                    <style>
                        .download-btn:hover {
                            transform: translateY(-3px);
                            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
                            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
                        }
                    </style>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Download page loaded - PC version only');
        
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

