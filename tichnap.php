<?php
session_start();
// Check if SepayService class exists before requiring
if (!class_exists('SepayService')) {
    require_once 'connection_manager.php';
    require_once 'includes/sepay_service.php';
    
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/auth_helper.php';

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$user_role = getUserRole();
$error = '';
$success = '';
$orderData = null;
$currentOrderCode = $_GET['order'] ?? '';

// Get current Silk amount
$silk = 0;
try {
    $db = ConnectionManager::getAccountDB();
    $stmt = $db->prepare("SELECT silk_own FROM SK_Silk WHERE JID = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $silk = $result['silk_own'];
    }
} catch (Exception $e) {
    // Continue with silk = 0 if error
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'QR_CODE'; // Default to QR_CODE for Sepay
    
    if ($amount < 10000 || $amount > 10000000) {
        $error = 'Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ!';
    } else {
        // Check if SepayService is available
        if (!class_exists('SepayService')) {
            $error = 'Lỗi hệ thống: SepayService class không tồn tại.';
        } else if (!method_exists('SepayService', 'createOrder')) {
            $error = 'Lỗi hệ thống: createOrder method không tồn tại.';
        } else {
            try {
                // Create order via SepayService
                $result = SepayService::createOrder(
                    $user_id, 
                    $username, 
                    $amount, 
                    $payment_method
                );
                
                if ($result['success']) {
                    $orderData = $result;
                    $currentOrderCode = $result['order_code'];
                    $success = 'Đã tạo order thành công! Vui lòng thanh toán theo thông tin bên dưới.';
                    
                    // Redirect to show order details
                    header('Location: payment.php?order=' . $currentOrderCode);
                    exit();
                } else {
                    $error = $result['error'] ?? 'Có lỗi xảy ra khi tạo order. Vui lòng thử lại!';
                }
            } catch (Exception $e) {
                $error = 'Lỗi khi tạo order: ' . $e->getMessage();
            } catch (Error $e) {
                $error = 'Lỗi nghiêm trọng khi tạo order: ' . $e->getMessage();
            }
        }
    }
} // End of if POST

// If order code in URL, get order data
if (!empty($currentOrderCode) && $orderData === null) {
    $result = SepayService::getOrderStatus($currentOrderCode);
    if ($result['success'] && $result['order']['JID'] == $user_id) {
        $orderData = $result['order'];
    } else {
        $error = $result['error'] ?? 'Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nạp Tích Lũy - Song Long Tranh Bá Mobile</title>
    
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
            min-height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        body.home-page {
            overflow-y: auto;
        }
        
        /* Dashboard layout with sidebar */
        .dashboard-wrapper {
            display: flex;
            position: relative;
            min-height: 100vh;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(15px);
        }
        
        /* Sidebar - Fixed position */
        .dashboard-sidebar {
            width: 260px;
            background: rgba(22, 33, 62, 0.95);
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
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
        .payment-container {
            flex: 1;
            margin-left: 260px;
            width: calc(100% - 260px);
            background: rgba(10, 20, 40, 0.95) !important;
            backdrop-filter: blur(20px);
            padding: 40px;
            min-height: 100vh;
            box-sizing: border-box;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        @keyframes glow {
            from { opacity: 0.5; }
            to { opacity: 0.8; }
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
        
        .payment-header {
            text-align: center;
            margin-bottom: 35px;
            padding-top: 20px;
            position: relative;
            z-index: 10;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #e8c088 !important;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #ffd700 !important;
            transform: translateX(-5px);
        }
        
        .payment-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .logo-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }
        
        .payment-header h1 {
            font-size: 28px;
            color: #e8c088 !important;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(232, 192, 136, 0.3);
        }

        /* TichNap Milestone Styles */
        #milestonesList button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        #milestonesList button:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .payment-container {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .dashboard-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .dashboard-sidebar.open {
                transform: translateX(0);
            }

            .menu-toggle {
                display: block;
            }

            #milestonesList {
                grid-template-columns: 1fr;
            }
        }
        
        .payment-header h2 {
            font-size: 24px;
            color: #e8c088 !important;
            margin: 15px 0 10px;
        }
        
        .payment-header p {
            color: #e8c088 !important;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .current-silk {
            background: rgba(30, 144, 255, 0.1) !important;
            border: 2px solid #1e90ff !important;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .current-silk h3 {
            color: #e8c088 !important;
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .silk-amount {
            font-size: 24px;
            font-weight: bold;
            color: #e8c088 !important;
        }
        
        .payment-form {
            background: rgba(20, 30, 50, 0.8) !important;
            border: 2px solid #4682b4 !important;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #e8c088 !important;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #4682b4 !important;
            border-radius: 8px;
            background: rgba(10, 20, 40, 0.8) !important;
            color: #e8c088 !important;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #1e90ff !important;
            box-shadow: 0 0 10px rgba(30, 144, 255, 0.5) !important;
            outline: none;
        }
        
        .amount-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .amount-btn {
            padding: 10px 15px;
            background: rgba(20, 30, 50, 0.8) !important;
            border: 2px solid #4682b4 !important;
            border-radius: 8px;
            color: #e8c088 !important;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-weight: bold;
        }
        
        .amount-btn:hover,
        .amount-btn.active {
            background: rgba(232, 192, 136, 0.1) !important;
            border-color: #e8c088 !important;
            color: #ffd700 !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e90ff, #00bfff) !important;
            border: 2px solid #1e90ff !important;
            color: #ffffff !important;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            font-size: 16px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #00bfff, #1e90ff) !important;
            box-shadow: 0 5px 15px rgba(30, 144, 255, 0.4) !important;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: transparent !important;
            border: 2px solid #4682b4 !important;
            color: #e8c088 !important;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn-secondary:hover {
            background: rgba(232, 192, 136, 0.1) !important;
            border-color: #e8c088 !important;
            color: #ffd700 !important;
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid;
        }
        
        .alert-success {
            background: rgba(0, 255, 0, 0.1) !important;
            border-color: #00ff00 !important;
            color: #00ff00 !important;
        }
        
        .alert-error {
            background: rgba(255, 0, 0, 0.1) !important;
            border-color: #ff0000 !important;
            color: #ff0000 !important;
        }
        
        .payment-info {
            background: rgba(30, 144, 255, 0.1) !important;
            border: 2px solid #1e90ff !important;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .payment-info h4 {
            color: #e8c088 !important;
            margin: 0 0 15px 0;
        }
        
        .payment-info p {
            color: #e8c088 !important;
            margin: 5px 0;
            font-size: 14px;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #1e90ff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.02); opacity: 0.9; }
        }
        
        #statusIndicator {
            transition: all 0.3s ease;
        }
        
        #statusIndicator.status-updated {
            animation: pulse 0.5s ease;
        }
        
        /* ========== RESPONSIVE - MOBILE ========== */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .dashboard-wrapper {
                display: block;
            }
            
            .dashboard-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .dashboard-sidebar.open {
                transform: translateX(0);
            }
            
            .payment-container {
                margin-left: 0;
                width: 100%;
                padding: 80px 15px 30px;
            }
            
            .amount-options {
                grid-template-columns: 1fr;
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
                <li><a href="tichnap.php" class="<?php echo getNavActiveClass('tichnap.php'); ?>"><i class="fas fa-gift"></i> Nạp Tích Lũy</a></li>
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
        <div class="payment-container">
            <div class="payment-header">
                <div class="payment-logo">
                    <img src="assets/images/logo.png" alt="Logo" class="logo-img">
                    <h1 class="f-utm_nyala t-upper">Song Long Tranh Bá</h1>
                </div>
                <h2 class="f-cambria">Nạp Tích Lũy</h2>
                <p class="f-calibri">Nhận phần thưởng khi đạt các mốc nạp tích lũy</p>
            </div>

            <!-- Nạp Tích Lũy Content -->
            <div class="payment-form" id="tichnapContent">
                <!-- Loading State -->
                <div id="tichnapLoading" style="text-align: center; padding: 40px; color: #e8c088;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <p>Đang tải thông tin...</p>
            </div>

                <!-- Error State -->
                <div id="tichnapError" style="display: none; text-align: center; padding: 40px; color: #ff6b6b;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <p id="errorMessage"></p>
                </div>

                <!-- Main Content -->
                <div id="tichnapMain" style="display: none;">
                    <!-- Thời gian sự kiện -->
                    <div id="eventTimeInfo" style="background: rgba(30, 144, 255, 0.1); border: 1px solid #1e90ff; border-radius: 6px; padding: 8px; margin-bottom: 12px; display: none;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
                            <div style="text-align: center;">
                                <div style="color: #87ceeb; font-size: 9px; margin-bottom: 3px;">
                                    <i class="fas fa-calendar-alt" style="font-size: 8px;"></i> Bắt đầu
                                </div>
                                <div style="color: #ffd700; font-size: 10px; font-weight: 600;" id="eventStartDate">
                                    --
                                </div>
                            </div>
                            <div style="color: #87ceeb; font-size: 12px;">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div style="text-align: center;">
                                <div style="color: #87ceeb; font-size: 9px; margin-bottom: 3px;">
                                    <i class="fas fa-calendar-check" style="font-size: 8px;"></i> Kết thúc
                                </div>
                                <div style="color: #ffd700; font-size: 10px; font-weight: 600;" id="eventEndDate">
                                    --
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tổng tiền đã nạp -->
                    <div style="background: rgba(30, 144, 255, 0.1); border: 1px solid #1e90ff; border-radius: 6px; padding: 10px; margin-bottom: 15px; text-align: center;">
                        <h3 style="color: #87ceeb; margin-bottom: 5px; font-size: 12px;">
                            <i class="fas fa-wallet" style="font-size: 11px;"></i> Tổng Tiền Đã Nạp
                        </h3>
                        <div style="font-size: 20px; font-weight: bold; color: #ffd700; text-shadow: 0 0 8px rgba(255, 215, 0, 0.4);" id="totalMoneyDisplay">
                            0 VND
                        </div>
                    </div>

                    <!-- Thông báo mốc tiếp theo -->
                    <div id="nextMilestoneInfo" style="background: rgba(255, 215, 0, 0.1); border: 1px solid #ffd700; border-radius: 6px; padding: 8px; margin-bottom: 15px; text-align: center; display: none;">
                        <p style="color: #ffd700; margin: 0; font-size: 11px;">
                            <i class="fas fa-info-circle" style="font-size: 10px;"></i> <span id="nextMilestoneText"></span>
                        </p>
                    </div>
                    
                    <!-- Danh sách mốc quà -->
                    <div id="milestonesList" style="display: flex; flex-direction: column; gap: 0;">
                        <!-- Milestones will be loaded here -->
                    </div>
                    </div>
                </div>

            <div class="payment-footer" style="text-align: center; margin-top: 30px;">
                <?php if (!$orderData || ($orderData['Status'] ?? '') !== 'pending'): ?>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-home"></i> Về Dashboard
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
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

    <!-- Scripts -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
    <script>
        // TichNap Feature - Load milestones and status
        (function() {
            const userJID = <?php echo $user_id; ?>;
            let totalMoney = 0;
            let milestones = [];
            let claimedMilestones = [];

            // Format VND
            function formatVND(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount) + ' VND';
            }

            // Format date time
            function formatDateTime(dateString) {
                if (!dateString) return '--';
                try {
                    const date = new Date(dateString);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${day}/${month}/${year} ${hours}:${minutes}`;
                } catch (e) {
                    return dateString;
                }
            }

            // Load all data
            async function loadTichNapData() {
                try {
                    // Load total money
                    const totalMoneyRes = await fetch(`api/tichnap/get_total_money.php?userJID=${userJID}`);
                    const totalMoneyData = await totalMoneyRes.json();
                    
                    if (totalMoneyData.success) {
                        totalMoney = totalMoneyData.data || 0;
                        document.getElementById('totalMoneyDisplay').textContent = formatVND(totalMoney);
                        
                        // Hiển thị thời gian sự kiện
                        const eventTimeInfo = document.getElementById('eventTimeInfo');
                        const eventStartDateEl = document.getElementById('eventStartDate');
                        const eventEndDateEl = document.getElementById('eventEndDate');
                        
                        if (totalMoneyData.eventStartDate || totalMoneyData.eventEndDate) {
                            eventStartDateEl.textContent = formatDateTime(totalMoneyData.eventStartDate);
                            eventEndDateEl.textContent = formatDateTime(totalMoneyData.eventEndDate);
                            eventTimeInfo.style.display = 'block';
                        } else {
                            eventTimeInfo.style.display = 'none';
                        }
                    }

                    // Check feature status
                    if (!totalMoneyData.featureEnabled || !totalMoneyData.inTimeRange) {
                        showError(totalMoneyData.featureMessage || 'Tính năng nạp tích lũy hiện không khả dụng');
                        return;
                    }

                    // Load milestones
                    const ranksRes = await fetch('api/tichnap/get_ranks.php');
                    const ranksData = await ranksRes.json();
                    
                    if (ranksData.success) {
                        milestones = ranksData.data || [];
                    }

                    // Load claimed status
                    const claimedRes = await fetch(`api/tichnap/get_claimed_status.php?username=<?php echo urlencode($username); ?>`);
                    const claimedData = await claimedRes.json();
                    
                    if (claimedData.success) {
                        claimedMilestones = claimedData.data.map(item => item.idItem);
                    }

                    // Render UI
                    renderMilestones();
                    updateNextMilestoneInfo();

                    // Show main content
                    document.getElementById('tichnapLoading').style.display = 'none';
                    document.getElementById('tichnapMain').style.display = 'block';

                } catch (error) {
                    console.error('Error loading TichNap data:', error);
                    showError('Lỗi khi tải dữ liệu: ' + error.message);
                }
            }

            // Show error
            function showError(message) {
                document.getElementById('tichnapLoading').style.display = 'none';
                document.getElementById('tichnapError').style.display = 'block';
                document.getElementById('errorMessage').textContent = message;
            }

            // Render milestones
            function renderMilestones() {
                const container = document.getElementById('milestonesList');
                
                if (!milestones || milestones.length === 0) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #e8c088;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>Chưa có mốc nạp tích lũy nào</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = milestones.map(milestone => {
                    const milestoneValue = milestone.priceValue || 0;
                    const isClaimed = claimedMilestones.includes(milestone.id);
                    const isReached = totalMoney >= milestoneValue;
                    const status = isClaimed ? 'claimed' : (isReached ? 'available' : 'locked');

                    let statusBadge = '';
                    let statusColor = '';
                    let buttonHtml = '';

                    if (status === 'claimed') {
                        statusBadge = '<span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 8px; font-size: 9px; font-weight: 600;"><i class="fas fa-check"></i> Đã Nhận</span>';
                        statusColor = '#28a745';
                    } else if (status === 'available') {
                        statusBadge = '<span style="background: #ffd700; color: #333; padding: 2px 6px; border-radius: 8px; font-size: 9px; font-weight: 600;"><i class="fas fa-gift"></i> Có Thể Nhận</span>';
                        statusColor = '#ffd700';
                        buttonHtml = `
                            <button onclick="claimReward('${milestone.id}')" 
                                    style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); 
                                           color: #333; 
                                           border: none; 
                                           padding: 5px 12px; 
                                           border-radius: 4px; 
                                           cursor: pointer; 
                                           font-weight: 600;
                                           font-size: 10px;
                                           width: 100%;
                                           transition: all 0.3s;">
                                <i class="fas fa-gift" style="font-size: 9px;"></i> Nhận Phần Thưởng
                            </button>
                        `;
                    } else {
                        statusBadge = '<span style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 8px; font-size: 9px; font-weight: 600;"><i class="fas fa-lock"></i> Chưa Đạt</span>';
                        statusColor = '#6c757d';
                        const remaining = milestoneValue - totalMoney;
                        buttonHtml = `
                            <div style="color: #87ceeb; font-size: 9px; text-align: center;">
                                <i class="fas fa-info-circle" style="font-size: 8px;"></i> Cần nạp thêm: <strong style="color: #ffd700;">${formatVND(remaining)}</strong>
                            </div>
                        `;
                    }

                    // Progress bar
                    const progressPercent = Math.min((totalMoney / milestoneValue) * 100, 100);

                    return `
                        <div style="background: rgba(30, 144, 255, 0.05); 
                                    border: 1px solid ${statusColor}; 
                                    border-radius: 6px; 
                                    padding: 6px 8px;
                                    margin-bottom: 4px;
                                    transition: all 0.3s;
                                    ${status === 'available' ? 'box-shadow: 0 0 10px rgba(255, 215, 0, 0.15);' : ''}">
                            <!-- Header: Title + Badge inline -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; gap: 6px;">
                                <div style="flex: 1; min-width: 0; display: flex; align-items: center; gap: 6px;">
                                    <h3 style="color: #e8c088; margin: 0; font-size: 13px; font-weight: 600; white-space: nowrap;">
                                        <i class="fas fa-trophy" style="font-size: 11px;"></i> ${milestone.price}
                                    </h3>
                                    ${statusBadge}
                                </div>
                            </div>

                            <!-- Compact Progress Bar -->
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <div style="flex: 1; background: rgba(108, 117, 125, 0.3); border-radius: 4px; height: 4px; overflow: hidden;">
                                    <div style="background: linear-gradient(90deg, ${statusColor} 0%, ${statusColor}dd 100%); 
                                               height: 100%; 
                                               width: ${progressPercent}%; 
                                               transition: width 0.5s;
                                               border-radius: 4px;"></div>
                                </div>
                                <div style="color: #87ceeb; font-size: 9px; white-space: nowrap; min-width: fit-content;">
                                    ${Math.round(progressPercent)}%
                                </div>
                            </div>

                            <!-- Compact Items List -->
                            ${milestone.items && milestone.items.length > 0 ? `
                                <div style="margin-bottom: 4px;">
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
                                        ${milestone.items.map(item => `
                                            <div style="display: inline-flex; align-items: center; gap: 4px; 
                                                       background: rgba(30, 144, 255, 0.08); 
                                                       border: 1px solid rgba(30, 144, 255, 0.2); 
                                                       border-radius: 4px; 
                                                       padding: 2px 6px;
                                                       font-size: 9px;">
                                                ${item.image ? `
                                                    <img src="${item.image}" 
                                                         alt="${item.name}" 
                                                         style="width: 16px; height: 16px; object-fit: contain;">
                                                ` : `
                                                    <i class="fas fa-box" style="font-size: 10px; color: #87ceeb;"></i>
                                                `}
                                                <span style="color: #e8c088; font-weight: 500;">${item.name}</span>
                                                <span style="color: #87ceeb; font-weight: 600;">x${item.quantity}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Action Button -->
                            ${buttonHtml ? `
                                <div style="margin-top: 4px;">
                                    ${buttonHtml}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('');
            }

            // Update next milestone info
            function updateNextMilestoneInfo() {
                const nextMilestone = milestones.find(m => {
                    const milestoneValue = m.priceValue || 0;
                    const isClaimed = claimedMilestones.includes(m.id);
                    return !isClaimed && totalMoney < milestoneValue;
                });

                const nextMilestoneInfo = document.getElementById('nextMilestoneInfo');
                const nextMilestoneText = document.getElementById('nextMilestoneText');

                if (nextMilestone) {
                    const remaining = nextMilestone.priceValue - totalMoney;
                    nextMilestoneText.textContent = `Vui lòng nạp thêm ${formatVND(remaining)} để nhận mốc tiếp theo (${nextMilestone.price})`;
                    nextMilestoneInfo.style.display = 'block';
                } else {
                    // All milestones reached or claimed
                    const allClaimed = milestones.every(m => claimedMilestones.includes(m.id));
                    if (allClaimed && milestones.length > 0) {
                        nextMilestoneText.textContent = 'Chúc mừng! Bạn đã nhận tất cả phần thưởng!';
                        nextMilestoneInfo.style.display = 'block';
                    } else {
                        nextMilestoneInfo.style.display = 'none';
                    }
                }
            }

            // Claim reward function
            window.claimReward = async function(milestoneId) {
                if (!confirm('Bạn có chắc muốn nhận phần thưởng này?')) {
                    return;
                }

                // Disable button to prevent double click
                const button = event.target.closest('button');
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                }

                try {
                    const response = await fetch('api/tichnap/claim_reward.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            itemTichNap: milestoneId,
                            userJID: userJID
                            // Không cần charNames nữa, API sẽ tự động lấy
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Đã nhận phần thưởng thành công!');
                        // Reload data
                        loadTichNapData();
                    } else {
                        // Hiển thị thông báo lỗi chi tiết
                        const errorMsg = result.error || 'Không thể nhận phần thưởng';
                        if (errorMsg.includes('đã nhận')) {
                            alert('⚠️ ' + errorMsg);
                        } else {
                            alert('❌ Lỗi: ' + errorMsg);
                        }
                        // Reload data để cập nhật trạng thái
                        loadTichNapData();
                    }
                } catch (error) {
                    alert('❌ Lỗi kết nối: ' + error.message);
                } finally {
                    // Re-enable button
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-gift"></i> Nhận Phần Thưởng';
                    }
                }
            };

            // Load data on page load
            loadTichNapData();
        })();
    </script>
    <script>
        // Initialize variables from PHP
        const ORDER_CODE = '<?php echo htmlspecialchars($currentOrderCode); ?>';
        const ORDER_STATUS = '<?php echo htmlspecialchars($orderData['Status'] ?? ''); ?>';
        
        // Function to calculate and display Silk amount
        function updateSilkPreview(amount) {
            if (amount && amount >= 10000) {
                const silkAmount = Math.floor(parseInt(amount) * 0.04);
                $('#silk-amount-preview').text(silkAmount.toLocaleString());
                $('#silk-preview').show();
            } else {
                $('#silk-preview').hide();
            }
        }
        
        $(document).ready(function() {
            // Amount selection
            $('.amount-btn').on('click', function() {
                $('.amount-btn').removeClass('active');
                $(this).addClass('active');
                const amount = $(this).data('amount');
                $('#amount').val(amount);
                updateSilkPreview(amount);
            });
            
            // Update Silk preview when user types
            $('#amount').on('input', function() {
                const amount = $(this).val();
                updateSilkPreview(amount);
            });
            
            // Copy button functionality
            $('.copy-btn').on('click', function() {
                const target = $(this).data('target');
                const text = $('#' + target).text().trim();
                
                // Create temporary input
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                
                // Show feedback
                $(this).html('<i class="fas fa-check"></i> Copied!');
                setTimeout(() => {
                    $(this).html('<i class="fas fa-copy"></i> Copy');
                }, 2000);
            });
            
            // Form validation and tracking
            $('#paymentForm').on('submit', function(e) {
                const amount = $('#amount').val();
                const paymentMethod = $('input[name="payment_method"]:checked').val() || 'QR_CODE';
                
                
                if (!amount || amount < 10000 || amount > 10000000) {
                    alert('Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ!');
                    e.preventDefault();
                    return false;
                }
                
                // Calculate Silk amount (100,000 VNĐ = 4,000 Silk)
                const silkAmount = Math.floor(parseInt(amount) * 0.04);
                const confirmMessage = `Xác nhận tạo đơn hàng ${parseInt(amount).toLocaleString()} VNĐ?\nBạn sẽ nhận được ${silkAmount.toLocaleString()} Silk.`;
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
                
            });
            
            // Initialize realtime status polling
            // Only poll if order is pending or processing
            // If already completed/failed/expired, no need to poll
            const hasOrderCode = ORDER_CODE && ORDER_CODE.trim().length > 0;
            const needsPolling = ORDER_STATUS === 'pending' || ORDER_STATUS === 'processing';
            
            if (hasOrderCode && needsPolling) {
                // Only start polling if order is still in progress
                initRealtimeStatusCheck();
            }
        });
        
        // Realtime status checking
        let statusPollInterval = null;
        let pollAttempts = 0;
        const maxPollAttempts = 200; // ~10 minutes (200 * 3s)
        
        function initRealtimeStatusCheck() {
            // Start polling
            startPolling();
        }
        
        function startPolling() {
            if (!ORDER_CODE || ORDER_CODE.trim().length === 0) {
                return;
            }
            
            const POLL_INTERVAL = 3000; // 3 seconds
            
            // Poll immediately first time
            checkOrderStatus();
            
            // Then poll every 3 seconds (fixed interval)
            statusPollInterval = setInterval(() => {
                checkOrderStatus();
            }, POLL_INTERVAL);
        }
        
        function stopPolling() {
            if (statusPollInterval) {
                clearInterval(statusPollInterval);
                statusPollInterval = null;
            }
        }
        
        async function checkOrderStatus() {
            if (pollAttempts >= maxPollAttempts) {
                stopPolling();
                updateStatus('timeout', 'Đã quá thời gian chờ. Vui lòng kiểm tra lại sau.');
                return;
            }
            
            pollAttempts++;
            
            // Show loading indicator
            showLoading(true);
            
            try {
                const apiUrl = `/api/sepay/get_order_status.php?order_code=${encodeURIComponent(ORDER_CODE)}&t=${Date.now()}`;
                
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success && data.order) {
                    const newStatus = data.order.Status;
                    
                    updateOrderStatus(data.order);
                    
                    // Stop polling if order is in final state
                    if (newStatus === 'completed' || 
                        newStatus === 'failed' || 
                        newStatus === 'cancelled' ||
                        newStatus === 'expired') {
                        stopPolling();
                        
                        if (newStatus === 'completed') {
                            handleCompleted(data.order);
                        } else {
                            updateStatus(newStatus, getStatusMessage(newStatus));
                        }
                    }
                }
            } catch (error) {
                // Continue polling on error (network issues, etc.)
                showError('Lỗi kết nối. Đang thử lại...');
            } finally {
                showLoading(false);
            }
        }
        
        let lastStatus = ORDER_STATUS;
        
        function updateOrderStatus(order) {
            const status = order.Status || order.status;
            const statusChanged = lastStatus !== status;
            
            if (statusChanged) {
                lastStatus = status;
            }
            
            updateStatus(status, getStatusMessage(status));
            
            // Update status badge
            const statusBadge = document.getElementById('orderStatusBadge');
            if (statusBadge) {
                const statusColors = {
                    'pending': { bg: '#ffc107', color: '#333' },
                    'processing': { bg: '#007bff', color: '#fff' },
                    'completed': { bg: '#28a745', color: '#fff' },
                    'failed': { bg: '#dc3545', color: '#fff' },
                    'expired': { bg: '#ffc107', color: '#333' }
                };
                
                const colors = statusColors[status.toLowerCase()] || statusColors.pending;
                statusBadge.style.background = colors.bg;
                statusBadge.style.color = colors.color;
                statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            }
            
            // Update status indicator with animation
            const statusIndicator = document.getElementById('statusIndicator');
            if (statusIndicator) {
                const statusClasses = {
                    'pending': 'rgba(255, 193, 7, 0.1)',
                    'processing': 'rgba(0, 123, 255, 0.1)',
                    'completed': 'rgba(40, 167, 69, 0.1)',
                    'failed': 'rgba(220, 53, 69, 0.1)',
                    'expired': 'rgba(255, 193, 7, 0.1)'
                };
                
                const statusColors = {
                    'pending': '#ffc107',
                    'processing': '#007bff',
                    'completed': '#28a745',
                    'failed': '#dc3545',
                    'expired': '#ffc107'
                };
                
                statusIndicator.style.background = statusClasses[status.toLowerCase()] || statusClasses.pending;
                statusIndicator.style.borderColor = statusColors[status.toLowerCase()] || statusColors.pending;
                
                // Add pulse animation when status changes
                if (statusChanged) {
                    statusIndicator.classList.add('status-updated');
                    setTimeout(() => {
                        statusIndicator.classList.remove('status-updated');
                    }, 500);
                }
            }
        }
        
        function updateStatus(status, message) {
            const statusText = document.getElementById('statusText');
            if (statusText) {
                statusText.innerHTML = message;
            }
        }
        
        function getStatusMessage(status) {
            const messages = {
                'pending': '<i class="fas fa-clock"></i> Đang chờ thanh toán...',
                'processing': '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...',
                'completed': '<i class="fas fa-check-circle"></i> ✅ Thanh toán thành công!',
                'failed': '<i class="fas fa-times-circle"></i> ❌ Thanh toán thất bại',
                'cancelled': '<i class="fas fa-ban"></i> ❌ Đơn hàng đã bị hủy',
                'expired': '<i class="fas fa-clock"></i> ⏰ Đơn hàng đã hết hạn'
            };
            
            return messages[status.toLowerCase()] || '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        }
        
        function showLoading(show) {
            const statusText = document.getElementById('statusText');
            if (statusText) {
                if (show) {
                    const currentText = statusText.innerHTML;
                    if (!currentText.includes('fa-spinner')) {
                        statusText.dataset.originalText = currentText;
                        statusText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
                    }
                } else {
                    const originalText = statusText.dataset.originalText;
                    if (originalText) {
                        statusText.innerHTML = originalText;
                        delete statusText.dataset.originalText;
                    }
                }
            }
        }
        
        function showError(message) {
            const statusText = document.getElementById('statusText');
            if (statusText) {
                statusText.innerHTML = `<span style="color: #ffc107;">⚠️ ${message}</span>`;
            }
        }
        
        function handleCompleted(order) {
            // Update status
            updateStatus('completed', '<i class="fas fa-check-circle"></i> ✅ Thanh toán thành công! Silk đã được cộng vào tài khoản.');
            
            // Show success message
            const successBox = $('<div class="alert alert-success" style="margin-top: 20px;">')
                .html(`
                    <i class="fas fa-check-circle"></i>
                    <strong>Thanh toán thành công!</strong><br>
                    Bạn đã nhận được <strong>${parseInt(order.SilkAmount || order.silk_amount || 0).toLocaleString()} Silk</strong>.
                    Vui lòng kiểm tra lại tài khoản.
                `);
            
            $('#orderDetailsSection').prepend(successBox);
            
            // Refresh page after 3 seconds to show updated silk
            setTimeout(() => {
                location.reload();
            }, 5000);
        }
    </script>
</body>
</html>
