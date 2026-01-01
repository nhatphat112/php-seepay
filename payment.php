<?php
session_start();
require_once 'connection_manager.php';
require_once 'payment_manager.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

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
    error_log("Error fetching silk for JID " . $user_id . ": " . $e->getMessage());
}

// Initialize payment manager
PaymentManager::init();
$gateways = PaymentManager::getGateways();
$methods = PaymentManager::getMethods();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = intval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $bank_code = $_POST['bank_code'] ?? '';
    $bank_name = $_POST['bank_name'] ?? '';
    
    if ($amount < 10000 || $amount > 10000000) {
        $error = 'Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ!';
    } elseif (empty($payment_method)) {
        $error = 'Vui lòng chọn phương thức thanh toán!';
    } else {
        // Create transaction
        $result = PaymentManager::createTransaction(
            $user_id, 
            $username, 
            $amount, 
            $payment_method, 
            $bank_code, 
            $bank_name,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        if ($result['success']) {
            // Process payment with gateway
            $method = array_filter($methods, function($m) use ($payment_method) {
                return $m['MethodCode'] === $payment_method;
            });
            $method = array_values($method)[0];
            
            $paymentResult = PaymentManager::processPayment($result['recharge_id'], $method['GatewayCode']);
            
            if ($paymentResult['success']) {
                // Redirect to payment gateway
                header('Location: ' . $paymentResult['payment_url']);
                exit();
            } else {
                $error = $paymentResult['error'];
            }
        } else {
            $error = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Con Đường Tơ Lụa Mobile</title>
    
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
        /* Payment overlay - Form nổi trên nền trang chính */
        .auth-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9) !important;
            backdrop-filter: blur(15px) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
            overflow-y: auto;
        }
        
        .payment-container {
            width: 100%;
            max-width: 900px;
            background: rgba(10, 20, 40, 0.95) !important;
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 2px solid #1e90ff !important;
            box-shadow: 0 20px 60px rgba(30, 144, 255, 0.3), 
                        0 0 0 1px rgba(30, 144, 255, 0.1),
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: slideIn 0.4s ease-out;
            margin: 20px auto;
            position: relative;
            overflow: hidden;
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
            border-radius: 10px;
        }
        
        .payment-header h1 {
            font-size: 28px;
            color: #1e90ff !important;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(30, 144, 255, 0.3);
        }
        
        .payment-header h2 {
            font-size: 24px;
            color: #ffffff !important;
            margin: 15px 0 10px;
        }
        
        .payment-header p {
            color: #87ceeb !important;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            margin-bottom: 5px;
        }
        
        .payment-method-desc {
            color: #87ceeb !important;
            font-size: 12px;
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
            color: #87ceeb !important;
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
            background: rgba(30, 144, 255, 0.1) !important;
            border-color: #1e90ff !important;
            color: #ffffff !important;
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
            color: #1e90ff !important;
            margin: 0 0 15px 0;
        }
        
        .payment-info p {
            color: #87ceeb !important;
            margin: 5px 0;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .payment-container {
                margin: 10px;
                padding: 20px;
            }
            
            .amount-options,
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="home-page">
    <!-- Payment Overlay - Form nổi trên nền trang chính -->
    <div class="auth-overlay">
        <div class="payment-container">
            <div class="payment-header">
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Quay lại Dashboard
                </a>
                <div class="payment-logo">
                    <img src="assets/images/logo.png" alt="Logo" class="logo-img">
                    <h1 class="f-utm_nyala t-upper">Con Đường Tơ Lụa</h1>
                </div>
                <h2 class="f-cambria">Thanh Toán Trực Tuyến</h2>
                <p class="f-calibri">Nạp Silk qua thẻ ATM, Internet Banking và ví điện tử</p>
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

            <!-- Payment Form -->
            <form method="POST" class="payment-form" id="paymentForm">
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
                        <?php foreach ($methods as $method): ?>
                            <div class="payment-method" data-method="<?php echo $method['MethodCode']; ?>" data-gateway="<?php echo $method['GatewayCode']; ?>">
                                <div class="payment-method-icon">
                                    <?php
                                    $icons = [
                                        'ATM_CARD' => 'fas fa-credit-card',
                                        'INTERNET_BANKING' => 'fas fa-university',
                                        'CREDIT_CARD' => 'fas fa-credit-card',
                                        'MOMO_WALLET' => 'fas fa-mobile-alt',
                                        'ZALOPAY_WALLET' => 'fas fa-wallet'
                                    ];
                                    $icon = $icons[$method['MethodCode']] ?? 'fas fa-credit-card';
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="payment-method-name"><?php echo $method['MethodName']; ?></div>
                                <div class="payment-method-desc">
                                    <?php
                                    $descriptions = [
                                        'ATM_CARD' => 'Thẻ ATM nội địa',
                                        'INTERNET_BANKING' => 'Internet Banking',
                                        'CREDIT_CARD' => 'Thẻ tín dụng quốc tế',
                                        'MOMO_WALLET' => 'Ví MoMo',
                                        'ZALOPAY_WALLET' => 'Ví ZaloPay'
                                    ];
                                    echo $descriptions[$method['MethodCode']] ?? $method['MethodName'];
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="payment_method" name="payment_method" required>
                    <input type="hidden" id="bank_code" name="bank_code" value="">
                    <input type="hidden" id="bank_name" name="bank_name" value="">
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-credit-card"></i> Thanh Toán Ngay
                </button>
            </form>

            <!-- Payment Information -->
            <div class="payment-info">
                <h4><i class="fas fa-info-circle"></i> Thông Tin Thanh Toán</h4>
                <p><strong>Tỷ lệ quy đổi:</strong> 1 VNĐ = 1 Silk</p>
                <p><strong>Thời gian xử lý:</strong> Tức thì sau khi thanh toán thành công</p>
                <p><strong>Phí giao dịch:</strong> Miễn phí</p>
                <p><strong>Bảo mật:</strong> Được mã hóa SSL 256-bit</p>
            </div>

            <div class="payment-footer" style="text-align: center; margin-top: 30px;">
                <a href="recharge.php" class="btn-secondary">
                    <i class="fas fa-university"></i> Chuyển Khoản Ngân Hàng
                </a>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-home"></i> Về Dashboard
                </a>
                <a href="debug_callback.php" class="btn-secondary" style="background: #ff6b6b;">
                    <i class="fas fa-bug"></i> Debug Callback
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
    <script>
        $(document).ready(function() {
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
                $('#bank_code').val($(this).data('gateway'));
                $('#bank_name').val($(this).find('.payment-method-name').text());
            });
            
            // Form validation
            $('#paymentForm').on('submit', function(e) {
                const amount = $('#amount').val();
                const method = $('#payment_method').val();
                
                if (!amount || amount < 10000 || amount > 10000000) {
                    alert('Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ!');
                    e.preventDefault();
                    return false;
                }
                
                if (!method) {
                    alert('Vui lòng chọn phương thức thanh toán!');
                    e.preventDefault();
                    return false;
                }
                
                if (!confirm(`Xác nhận thanh toán ${parseInt(amount).toLocaleString()} VNĐ?\nBạn sẽ nhận được ${parseInt(amount).toLocaleString()} Silk.`)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
