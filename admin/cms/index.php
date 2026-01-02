<?php
/**
 * CMS Dashboard - Trang chính quản lý CMS
 * TODO: Thêm authentication sau
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Dashboard - Con Đường Tơ Lụa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #fff;
            line-height: 1.6;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: #16213e;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #0f1624;
            margin-bottom: 20px;
        }
        
        .sidebar-header h1 {
            font-size: 1.5rem;
            color: #e8c088;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 0.85rem;
            color: #87ceeb;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-menu li {
            margin: 5px 0;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            background: #0f1624;
            border-left-color: #e8c088;
            color: #e8c088;
        }
        
        .nav-menu a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            font-size: 2rem;
            color: #e8c088;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #87ceeb;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .dashboard-card {
            background: #16213e;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(232, 192, 136, 0.2);
        }
        
        .dashboard-card-icon {
            font-size: 2.5rem;
            color: #e8c088;
            margin-bottom: 15px;
        }
        
        .dashboard-card h3 {
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 10px;
        }
        
        .dashboard-card p {
            color: #87ceeb;
            font-size: 0.9rem;
        }
        
        .dashboard-card a {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 20px;
            background: #e8c088;
            color: #1a1a2e;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .dashboard-card a:hover {
            background: #ffd700;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-cog"></i> CMS Dashboard</h1>
                <p>Quản lý nội dung</p>
            </div>
            <ul class="nav-menu">
                <li><a href="/cms" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="/admin/slider.php"><i class="fas fa-images"></i> Slider (5 ảnh)</a></li>
                <li><a href="/admin/news.php"><i class="fas fa-newspaper"></i> Tin Bài</a></li>
                <li><a href="/admin/social.php"><i class="fas fa-share-alt"></i> Social Links</a></li>
                <li><a href="/admin/server_info.php"><i class="fas fa-server"></i> Thông Tin Server</a></li>
                <li><a href="/admin/weekly_events.php"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="/admin/qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="/admin/orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2>Dashboard</h2>
                <p>Quản lý nội dung trang chủ và xem lịch sử giao dịch</p>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <h3>Slider</h3>
                    <p>Quản lý 5 ảnh slide ở đầu trang</p>
                    <a href="/admin/slider.php">Quản lý →</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <h3>Tin Bài</h3>
                    <p>Quản lý tin tức sự kiện, copy link từ FB</p>
                    <a href="/admin/news.php">Quản lý →</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h3>Social Links</h3>
                    <p>Quản lý link FB, Zalo, Group FB, Discord</p>
                    <a href="/admin/social.php">Quản lý →</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3>Thông Tin Server</h3>
                    <p>Edit thông tin server (text area)</p>
                    <a href="/admin/server_info.php">Quản lý →</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <h3>Sự Kiện Trong Tuần</h3>
                    <p>Quản lý 5 sự kiện trong tuần</p>
                    <a href="/admin/weekly_events.php">Quản lý →</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3>QR Code</h3>
                    <p>Upload ảnh QR code</p>
                    <a href="/admin/qrcode.php">Quản lý →</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Lịch Sử Giao Dịch</h3>
                    <p>Xem và tìm kiếm orders</p>
                    <a href="/admin/orders.php">Xem →</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

