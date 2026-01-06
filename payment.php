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
    <title>Thanh Toán - Song Long Tranh Bá Mobile</title>
    
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
        .payment-container {
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
        }
        
        /* Blue glow effect */
        .payment-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #1e90ff, #00bfff, #1e90ff);
            border-radius: 20px;
            z-index: -1;
            animation: glow 2s ease-in-out infinite alternate;
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
            
            .payment-container {
                margin-left: 0;
                max-width: 100%;
                padding: 60px 15px 30px;
                overflow-x: hidden;
                overflow-y: visible;
                min-height: auto;
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Trang Chủ</a></li>
                <li><a href="transaction_history.php"><i class="fas fa-history"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="payment.php" class="active"><i class="fas fa-credit-card"></i> Nạp Tiền</a></li>
                <li><a href="download.php"><i class="fas fa-download"></i> Tải Game</a></li>
                <li><a href="ranking.php"><i class="fas fa-trophy"></i> Xếp Hạng</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin/cms/index.php"><i class="fas fa-cog"></i> CMS Admin</a></li>
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
                <h2 class="f-cambria">Nạp Tiền</h2>
                <p class="f-calibri">Nạp Silk qua QR Code và Chuyển Khoản Ngân Hàng</p>
            </div>

            <!-- Current Silk -->
            <div class="current-silk">
                <h3><i class="fas fa-gem"></i> Silk Hiện Tại</h3>
                <div class="silk-amount"><?php echo number_format($silk); ?> Silk</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($orderData && ($orderData['Status'] === 'pending' || $orderData['Status'] === 'processing')): ?>
                <!-- Order Details Section -->
                <div class="payment-form" id="orderDetailsSection">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                        <i class="fas fa-receipt"></i> Chi Tiết Đơn Hàng #<?php echo htmlspecialchars($orderData['OrderCode']); ?>
                    </h3>
                    
                    <!-- Status Indicator with Realtime Updates -->
                    <div id="statusIndicator" style="background: rgba(255, 193, 7, 0.1); border: 2px solid #ffc107; border-radius: 10px; padding: 15px; margin-bottom: 20px; text-align: center;">
                        <div id="statusText" style="color: #e8c088; font-weight: bold; font-size: 16px;">
                            <i class="fas fa-circle-notch fa-spin"></i> Đang chờ thanh toán...
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Trạng thái:</span>
                            <span style="color: #e8c088;">
                                <span id="orderStatusBadge" style="font-weight: bold; padding: 5px 10px; border-radius: 5px; background: #ffc107; color: #333;">
                                    <?php echo htmlspecialchars(ucfirst($orderData['Status'])); ?>
                                </span>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Số tiền:</span>
                            <span style="color: #e8c088;"><?php echo number_format($orderData['Amount']); ?> VNĐ</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Số Silk nhận:</span>
                            <span style="color: #e8c088;"><?php echo number_format($orderData['SilkAmount']); ?> Silk</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Thời gian tạo:</span>
                            <span style="color: #e8c088;"><?php echo date('H:i:s d-m-Y', strtotime($orderData['CreatedDate'])); ?></span>
                        </div>
                    </div>

                    <?php
                    if (!empty($orderData['QRCode'])): ?>
                        <div style="text-align: center; margin-top: 20px; padding: 20px; background: rgba(10, 20, 40, 0.8); border-radius: 10px; border: 1px solid #4682b4;">
                            <h4 style="color: #e8c088; margin: 0 0 15px 0; font-size: 16px;">
                                <i class="fas fa-qrcode"></i> Mã QR Thanh Toán
                            </h4>
                            <div style="display: inline-block; padding: 15px; background: #ffffff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);">
                                <img 
                                    src="<?php echo htmlspecialchars($orderData['QRCode']); ?>" 
                                    alt="QR Code" 
                                    id="qr-code-image"
                                    style="max-width: 250px; width: 100%; height: auto; display: block; border-radius: 5px;"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'250\' height=\'250\'%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23ccc\'%3EQR Code không tải được%3C/text%3E%3C/svg%3E';"
                                >
                            </div>
                            <p style="color: #e8c088; font-size: 14px; margin: 15px 0 10px 0; font-weight: bold;">Quét mã QR để thanh toán</p>
                            <p style="color: #e8c088; font-size: 12px; margin: 0;">Hoặc chuyển khoản theo thông tin bên dưới</p>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; margin-top: 20px; padding: 20px; background: rgba(255, 193, 7, 0.1); border-radius: 10px; border: 1px solid #ffc107;">
                            <p style="color: #ffc107; font-size: 14px;">
                                <i class="fas fa-exclamation-triangle"></i> QR Code đang được tạo, vui lòng đợi...
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($orderData['BankAccount'])): ?>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                            <thead>
                                <tr>
                                    <th style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left; background: rgba(30, 144, 255, 0.2); color: #e8c088;">Thông tin chuyển khoản</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left; background: rgba(30, 144, 255, 0.2); color: #e8c088;">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orderData['BankName'])): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">Ngân hàng</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;"><?php echo htmlspecialchars($orderData['BankName']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">Số tài khoản</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">
                                        <span id="bankAccountNum"><?php echo htmlspecialchars($orderData['BankAccount']); ?></span>
                                        <button class="copy-btn" data-target="bankAccountNum" style="background: #1e90ff; color: #fff; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; margin-left: 10px;">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                                <?php if (!empty($orderData['AccountName'])): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">Tên tài khoản</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;"><?php echo htmlspecialchars($orderData['AccountName']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($orderData['Content'])): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">Nội dung chuyển khoản</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">
                                        <span id="transferContent"><?php echo htmlspecialchars($orderData['Content']); ?></span>
                                        <button class="copy-btn" data-target="transferContent" style="background: #1e90ff; color: #fff; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; margin-left: 10px;">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">Số tiền</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(20, 30, 50, 0.8); color: #e8c088;">
                                        <span id="transferAmount"><?php echo number_format($orderData['Amount']); ?></span> VNĐ
                                        <button class="copy-btn" data-target="transferAmount" style="background: #1e90ff; color: #fff; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; margin-left: 10px;">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <div style="background: rgba(30, 144, 255, 0.1); border: 2px solid #1e90ff; border-radius: 10px; padding: 20px; margin-top: 30px;">
                        <h4 style="color: #e8c088; margin: 0 0 15px 0;">
                            <i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng
                        </h4>
                        <p style="color: #e8c088; margin: 5px 0; font-size: 14px;">- Vui lòng chuyển khoản đúng số tiền và nội dung để giao dịch được xử lý tự động.</p>
                        <p style="color: #e8c088; margin: 5px 0; font-size: 14px;">- Nếu chuyển sai nội dung hoặc số tiền, giao dịch có thể bị treo và cần liên hệ hỗ trợ.</p>
                        <p style="color: #e8c088; margin: 5px 0; font-size: 14px;">- Đơn hàng sẽ hết hạn sau 15 phút. Vui lòng hoàn tất thanh toán trong thời gian này.</p>
                    </div>
                </div>
            <?php elseif ($orderData && ($orderData['Status'] === 'completed' || $orderData['Status'] === 'failed' || $orderData['Status'] === 'expired')): ?>
                <!-- Final Status Display -->
                <div class="payment-form">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                        <i class="fas fa-info-circle"></i> Trạng Thái Đơn Hàng #<?php echo htmlspecialchars($orderData['OrderCode']); ?>
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Trạng thái:</span>
                            <span style="font-weight: bold; padding: 5px 10px; border-radius: 5px; 
                                <?php 
                                $statusColor = $orderData['Status'] === 'completed' ? 'background: #28a745; color: #fff;' : 
                                              ($orderData['Status'] === 'failed' ? 'background: #dc3545; color: #fff;' : 
                                              'background: #ffc107; color: #333;');
                                echo $statusColor;
                                ?>">
                                <?php echo htmlspecialchars(ucfirst($orderData['Status'])); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Số tiền:</span>
                            <span style="color: #e8c088;"><?php echo number_format($orderData['Amount']); ?> VNĐ</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Số Silk nhận:</span>
                            <span style="color: #e8c088;"><?php echo number_format($orderData['SilkAmount']); ?> Silk</span>
                        </div>
                        <?php if (!empty($orderData['CompletedDate'])): ?>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 5px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
                            <span style="color: #e8c088; font-weight: bold;">Thời gian hoàn tất:</span>
                            <span style="color: #e8c088;"><?php echo date('H:i:s d-m-Y', strtotime($orderData['CompletedDate'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Payment Form Section (for new order) -->
            <form method="POST" class="payment-form" id="paymentForm">
                <div class="form-group">
                    <label for="amount">Số Tiền Nạp (VNĐ)</label>
                    <input type="number" id="amount" name="amount" class="form-control" 
                           placeholder="Nhập số tiền..." min="10000" max="10000000" required>
                    <div id="silk-preview" style="margin-top: 10px; padding: 10px; background: rgba(30, 144, 255, 0.1); border-radius: 5px; border: 1px solid #1e90ff; display: none;">
                        <span style="color: #e8c088; font-weight: bold;">Bạn sẽ nhận được: </span>
                        <span id="silk-amount-preview" style="color: #e8c088; font-weight: bold; font-size: 18px;">0</span>
                        <span style="color: #e8c088; font-weight: bold;"> Silk</span>
                    </div>
                    
                    <div class="amount-options">
                        <div class="amount-btn" data-amount="50000">
                            <div>50,000 VNĐ</div>
                            <div style="font-size: 12px; color: #e8c088; margin-top: 5px;">= 2,000 Silk</div>
                        </div>
                        <div class="amount-btn" data-amount="100000">
                            <div>100,000 VNĐ</div>
                            <div style="font-size: 12px; color: #e8c088; margin-top: 5px;">= 4,000 Silk</div>
                        </div>
                        <div class="amount-btn" data-amount="200000">
                            <div>200,000 VNĐ</div>
                            <div style="font-size: 12px; color: #e8c088; margin-top: 5px;">= 8,000 Silk</div>
                        </div>
                        <div class="amount-btn" data-amount="500000">
                            <div>500,000 VNĐ</div>
                            <div style="font-size: 12px; color: #e8c088; margin-top: 5px;">= 20,000 Silk</div>
                        </div>
                        <div class="amount-btn" data-amount="1000000">
                            <div>1,000,000 VNĐ</div>
                            <div style="font-size: 12px; color: #e8c088; margin-top: 5px;">= 40,000 Silk</div>
                        </div>
                        <div class="amount-btn" data-amount="2000000">
                            <div>2,000,000 VNĐ</div>
                            <div style="font-size: 12px; color: #e8c088; margin-top: 5px;">= 80,000 Silk</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-credit-card"></i> Thanh Toán Ngay
                </button>
            </form>

            <!-- Payment Information -->
            <div class="payment-info">
                <h4><i class="fas fa-info-circle"></i> Thông Tin Thanh Toán</h4>
                <p><strong>Tỷ lệ quy đổi:</strong> 100,000 VNĐ = 4,000 Silk (1 VNĐ = 0.04 Silk)</p>
                <p><strong>Thời gian xử lý:</strong> Tức thì sau khi thanh toán thành công</p>
                <p><strong>Phí giao dịch:</strong> Miễn phí</p>
                <p><strong>Bảo mật:</strong> Được mã hóa SSL 256-bit</p>
            </div>
            <?php endif; ?>

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
