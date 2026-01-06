<?php
session_start();
require_once 'connection_manager.php';
require_once 'includes/config.php';
require_once 'includes/sepay_service.php';

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
    $silkDb = ConnectionManager::getAccountDB();
    $stmt = $silkDb->prepare("SELECT silk_own FROM SK_Silk WHERE JID = ?");
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
    $payment_method = $_POST['payment_method'] ?? 'QR_CODE';
    
    if ($amount < 10000 || $amount > 10000000) {
        $error = 'Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ!';
    } else {
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
        } else {
            $error = $result['error'] ?? 'Có lỗi xảy ra khi tạo order. Vui lòng thử lại!';
        }
    }
}

// If order code in URL, get order data
if (!empty($currentOrderCode) && $orderData === null) {
    $result = SepayService::getOrderStatus($currentOrderCode);
    if ($result['success']) {
        $orderData = $result['order'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp Tiền - Song Long Tranh Bá Mobile</title>
    
    <link rel="icon" href="images/favicon.ico"/>
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
            overflow: hidden;
        }
        
        body.home-page {
            overflow: hidden;
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
        
        /* Main content - Không dùng position fixed, đẩy sang phải */
        .payment-container {
            flex: 1;
            margin-left: 260px;
            width: calc(100% - 260px);
            max-width: calc(100% - 260px);
            background: rgba(10, 20, 40, 0.95) !important;
            backdrop-filter: blur(20px);
            padding: 40px;
            min-height: 100vh;
            box-sizing: border-box;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
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
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #87ceeb !important;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #1e90ff !important;
            transform: translateX(-5px);
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
            color: #1e90ff !important;
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .silk-amount {
            font-size: 24px;
            font-weight: bold;
            color: #87ceeb !important;
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
            color: #87ceeb !important;
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
            color: #ffffff !important;
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
            color: #87ceeb !important;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-weight: bold;
        }
        
        .amount-btn:hover,
        .amount-btn.active {
            background: rgba(30, 144, 255, 0.1) !important;
            border-color: #1e90ff !important;
            color: #ffffff !important;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            background: rgba(20, 30, 50, 0.8) !important;
            border: 2px solid #4682b4 !important;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .payment-method:hover,
        .payment-method.active {
            background: rgba(30, 144, 255, 0.1) !important;
            border-color: #1e90ff !important;
        }
        
        .payment-method-icon {
            font-size: 32px;
            color: #1e90ff !important;
            margin-bottom: 10px;
        }
        
        .payment-method-name {
            color: #87ceeb !important;
            font-weight: bold;
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
        
        .payment-info-box {
            background: rgba(30, 144, 255, 0.1) !important;
            border: 2px solid #1e90ff !important;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            display: none;
        }
        
        .payment-info-box.active {
            display: block;
        }
        
        .qr-code-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .qr-code-image {
            max-width: 300px;
            width: 100%;
            height: auto;
            border: 3px solid #1e90ff;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }
        
        .bank-info {
            background: rgba(20, 30, 50, 0.8) !important;
            border: 2px solid #4682b4 !important;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .bank-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .bank-info-item:last-child {
            border-bottom: none;
        }
        
        .bank-info-label {
            color: #87ceeb !important;
            font-weight: bold;
        }
        
        .bank-info-value {
            color: #ffffff !important;
            font-family: monospace;
            font-size: 16px;
        }
        
        .copy-btn {
            background: rgba(30, 144, 255, 0.2) !important;
            border: 1px solid #1e90ff !important;
            color: #1e90ff !important;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: rgba(30, 144, 255, 0.3) !important;
        }
        
        .status-indicator {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.1) !important;
            border: 2px solid #ffc107 !important;
            color: #ffc107 !important;
        }
        
        .status-processing {
            background: rgba(0, 123, 255, 0.1) !important;
            border: 2px solid #007bff !important;
            color: #007bff !important;
        }
        
        .status-completed {
            background: rgba(40, 167, 69, 0.1) !important;
            border: 2px solid #28a745 !important;
            color: #28a745 !important;
        }
        
        .status-failed {
            background: rgba(220, 53, 69, 0.1) !important;
            border: 2px solid #dc3545 !important;
            color: #dc3545 !important;
        }
        
        .countdown-timer {
            font-size: 18px;
            font-weight: bold;
            color: #ffc107 !important;
            margin-top: 10px;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #1e90ff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .status-indicator {
            transition: all 0.3s ease;
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
                <h1 class="f-utm_nyala t-upper" style="color: #1e90ff !important;">Nạp Tiền</h1>
                <p class="f-calibri" style="color: #87ceeb !important;">Nạp Silk qua QR Code và Chuyển Khoản</p>
            </div>

            <!-- Current Silk -->
            <div class="current-silk">
                <h3><i class="fas fa-gem"></i> Silk Hiện Tại</h3>
                <div class="silk-amount" id="currentSilk"><?php echo number_format($silk); ?> Silk</div>
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

            <!-- Payment Form (hidden when order is created) -->
            <form method="POST" class="payment-form" id="paymentForm" style="<?php echo $orderData ? 'display: none;' : ''; ?>">
                <div class="form-group">
                    <label for="amount">Số Tiền Nạp (VNĐ)</label>
                    <input type="number" id="amount" name="amount" class="form-control" 
                           placeholder="Nhập số tiền..." min="10000" max="10000000" required>
                    
                    <div class="amount-options">
                        <div class="amount-btn" data-amount="50000">50,000 VNĐ</div>
                        <div class="amount-btn" data-amount="100000">100,000 VNĐ</div>
                        <div class="amount-btn" data-amount="200000">200,000 VNĐ</div>
                        <div class="amount-btn" data-amount="500000">500,000 VNĐ</div>
                        <div class="amount-btn" data-amount="1000000">1,000,000 VNĐ</div>
                        <div class="amount-btn" data-amount="2000000">2,000,000 VNĐ</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Chọn Phương Thức Thanh Toán</label>
                    <div class="payment-methods">
                        <div class="payment-method active" data-method="QR_CODE">
                            <div class="payment-method-icon">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="payment-method-name">QR Code</div>
                        </div>
                        <div class="payment-method" data-method="BANK_TRANSFER">
                            <div class="payment-method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="payment-method-name">Chuyển Khoản</div>
                        </div>
                    </div>
                    <input type="hidden" id="payment_method" name="payment_method" value="QR_CODE" required>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-credit-card"></i> Tạo Order Thanh Toán
                </button>
            </form>

            <!-- Payment Info Box (shown when order is created) -->
            <div class="payment-info-box <?php echo $orderData ? 'active' : ''; ?>" id="paymentInfoBox">
                <?php if ($orderData): ?>
                    <!-- Status Indicator -->
                    <div class="status-indicator status-<?php echo strtolower($orderData['Status'] ?? 'pending'); ?>" id="statusIndicator">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span id="statusText">Đang chờ thanh toán...</span>
                        <div class="countdown-timer" id="countdownTimer"></div>
                    </div>

                    <!-- QR Code -->
                    <?php if (!empty($orderData['QRCode'] ?? $orderData['qr_code'] ?? '')): ?>
                        <div class="qr-code-container">
                            <h3 style="color: #1e90ff !important; margin-bottom: 20px;">
                                <i class="fas fa-qrcode"></i> Quét QR Code để thanh toán
                            </h3>
                            <img src="<?php echo htmlspecialchars($orderData['QRCode'] ?? $orderData['qr_code']); ?>" 
                                 alt="QR Code" class="qr-code-image" id="qrCodeImage">
                        </div>
                    <?php endif; ?>

                    <!-- Bank Info -->
                    <?php if (!empty($orderData['BankAccount'] ?? $orderData['bank_account'] ?? '')): ?>
                        <div class="bank-info">
                            <h3 style="color: #1e90ff !important; margin-bottom: 20px;">
                                <i class="fas fa-university"></i> Thông Tin Chuyển Khoản
                            </h3>
                            
                            <div class="bank-info-item">
                                <span class="bank-info-label">Số tài khoản:</span>
                                <span class="bank-info-value">
                                    <span id="bankAccount"><?php echo htmlspecialchars($orderData['BankAccount'] ?? $orderData['bank_account'] ?? ''); ?></span>
                                    <button class="copy-btn" onclick="copyToClipboard('bankAccount')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </span>
                            </div>
                            
                            <?php if (!empty($orderData['BankName'] ?? $orderData['bank_name'] ?? '')): ?>
                            <div class="bank-info-item">
                                <span class="bank-info-label">Ngân hàng:</span>
                                <span class="bank-info-value"><?php echo htmlspecialchars($orderData['BankName'] ?? $orderData['bank_name'] ?? ''); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($orderData['AccountName'] ?? $orderData['account_name'] ?? '')): ?>
                            <div class="bank-info-item">
                                <span class="bank-info-label">Tên chủ tài khoản:</span>
                                <span class="bank-info-value"><?php echo htmlspecialchars($orderData['AccountName'] ?? $orderData['account_name'] ?? ''); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($orderData['Content'] ?? $orderData['content'] ?? '')): ?>
                            <div class="bank-info-item">
                                <span class="bank-info-label">Nội dung chuyển khoản:</span>
                                <span class="bank-info-value">
                                    <span id="transferContent"><?php echo htmlspecialchars($orderData['Content'] ?? $orderData['content'] ?? ''); ?></span>
                                    <button class="copy-btn" onclick="copyToClipboard('transferContent')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="bank-info-item">
                                <span class="bank-info-label">Số tiền:</span>
                                <span class="bank-info-value" style="color: #ffc107 !important; font-size: 18px;">
                                    <?php echo number_format($orderData['Amount'] ?? $orderData['amount'] ?? 0); ?> VNĐ
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Order Info -->
                    <div style="text-align: center; margin-top: 20px; color: #87ceeb !important;">
                        <p>Mã đơn hàng: <strong style="color: #1e90ff !important;"><?php echo htmlspecialchars($orderData['OrderCode'] ?? $orderData['order_code'] ?? ''); ?></strong></p>
                        <p style="font-size: 12px; margin-top: 10px;">
                            <i class="fas fa-info-circle"></i> 
                            Sau khi thanh toán thành công, Silk sẽ được cộng tự động trong vòng vài giây.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
    <script src="js/payment-sepay.js"></script>
    <script>
        // Initialize PaymentSepay with realtime status updates
        (function() {
            const orderCode = '<?php echo htmlspecialchars($currentOrderCode); ?>';
            const expiredAt = '<?php echo $orderData['ExpiredAt'] ?? $orderData['expired_at'] ?? ''; ?>';
            
            console.log('PaymentSepay: Initializing realtime status check...');
            console.log('PaymentSepay: Order code:', orderCode);
            console.log('PaymentSepay: Expired at:', expiredAt);
            
            if (orderCode && expiredAt) {
                // Wait for DOM to be ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('PaymentSepay: DOM ready, starting realtime polling...');
                        PaymentSepay.init(orderCode, expiredAt);
                        window.paymentSepayInstance = PaymentSepay; // Store for debugging
                    });
                } else {
                    console.log('PaymentSepay: DOM already ready, starting realtime polling...');
                    PaymentSepay.init(orderCode, expiredAt);
                    window.paymentSepayInstance = PaymentSepay; // Store for debugging
                }
            } else {
                console.log('PaymentSepay: No order code or expired date, skipping initialization');
                if (!orderCode) console.warn('PaymentSepay: Missing order code');
                if (!expiredAt) console.warn('PaymentSepay: Missing expired date');
            }
        })();
        
        // Amount selection
        $('.amount-btn').on('click', function() {
            $('.amount-btn').removeClass('active');
            $(this).addClass('active');
            $('#amount').val($(this).data('amount'));
        });
        
        // Payment method selection
        $('.payment-method').on('click', function() {
            $('.payment-method').removeClass('active');
            $(this).addClass('active');
            $('#payment_method').val($(this).data('method'));
        });
        
        // Copy to clipboard
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();
            
            navigator.clipboard.writeText(text).then(function() {
                const btn = event.target.closest('.copy-btn');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = 'rgba(40, 167, 69, 0.3) !important';
                btn.style.borderColor = '#28a745 !important';
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                    btn.style.borderColor = '';
                }, 2000);
            }).catch(function(err) {
                alert('Không thể copy. Vui lòng copy thủ công: ' + text);
            });
        }
        
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
    
    <style>
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
                max-width: 100%;
                padding: 80px 15px 30px;
            }
            
            /* Các element khác responsive */
            .amount-options {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
            
            .qr-code-image {
                max-width: 250px;
            }
            
            .bank-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .bank-info-value {
                width: 100%;
                word-break: break-all;
            }
        }
    </style>
</body>
</html>

