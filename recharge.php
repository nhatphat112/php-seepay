<?php
session_start();
require_once 'connection_manager.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = intval($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? '';
    $bank_code = $_POST['bank_code'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? '';
    
    if ($amount < 10000 || $amount > 10000000) {
        $error = 'Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ!';
    } elseif (empty($method) || empty($bank_code)) {
        $error = 'Vui lòng chọn phương thức thanh toán và ngân hàng!';
    } elseif (empty($transaction_id)) {
        $error = 'Vui lòng nhập mã giao dịch!';
    } else {
        // Calculate Silk amount (1 VNĐ = 1 Silk)
        $silk_amount = $amount;
        
        try {
            // Update Silk in database
            $stmt = $silkDb->prepare("
                UPDATE SK_Silk 
                SET silk_own = silk_own + ? 
                WHERE JID = ?
            ");
            $stmt->execute([$silk_amount, $user_id]);
            
            // Log transaction
            $logDb = ConnectionManager::getLogDB();
            $logStmt = $logDb->prepare("
                INSERT INTO _LogEventUser (UserJID, EventID, EventData, RegDate)
                VALUES (?, 3, ?, GETDATE())
            ");
            $logData = "Recharge: $amount VNĐ via $method-$bank_code, Transaction: $transaction_id, Silk: +$silk_amount";
            $logStmt->execute([$user_id, $logData]);
            
            $success = "Nạp thẻ thành công! Bạn đã nhận được " . number_format($silk_amount) . " Silk.";
            
            // Refresh Silk amount
            $stmt = $silkDb->prepare("SELECT silk_own FROM SK_Silk WHERE JID = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $silk = $result['silk_own'];
            }
            
        } catch (Exception $e) {
            error_log("Recharge error: " . $e->getMessage());
            $error = 'Có lỗi xảy ra khi nạp thẻ. Vui lòng thử lại!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp Thẻ - Con Đường Tơ Lụa Mobile</title>
    
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
        /* Recharge overlay - Form nổi trên nền trang chính */
        .auth-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9) !important; /* Nền tối hơn để rõ hơn */
            backdrop-filter: blur(15px) !important; /* Blur mạnh hơn */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
            overflow-y: auto;
        }
        
        .recharge-container {
            width: 100%;
            max-width: 800px;
            background: rgba(10, 20, 40, 0.95) !important; /* Nền xanh tối */
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 2px solid #1e90ff !important; /* Viền xanh nước biển */
            box-shadow: 0 20px 60px rgba(30, 144, 255, 0.3), 
                        0 0 0 1px rgba(30, 144, 255, 0.1),
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: slideIn 0.4s ease-out;
            margin: 20px auto;
            position: relative;
            overflow: hidden;
        }
        
        /* Blue glow effect */
        .recharge-container::before {
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
        
        .recharge-header {
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
        
        .recharge-logo {
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
        
        .recharge-header h1 {
            font-size: 28px;
            color: #1e90ff !important;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(30, 144, 255, 0.3);
        }
        
        .recharge-header h2 {
            font-size: 24px;
            color: #ffffff !important;
            margin: 15px 0 10px;
        }
        
        .recharge-header p {
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
        
        .recharge-form {
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
        
        .bank-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .bank-btn {
            padding: 15px;
            background: rgba(20, 30, 50, 0.8) !important;
            border: 2px solid #4682b4 !important;
            border-radius: 10px;
            color: #87ceeb !important;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .bank-btn:hover,
        .bank-btn.active {
            background: rgba(30, 144, 255, 0.1) !important;
            border-color: #1e90ff !important;
            color: #ffffff !important;
        }
        
        .bank-icon {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
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
        
        .bank-account {
            background: rgba(20, 30, 50, 0.8) !important;
            border: 1px solid #4682b4 !important;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .bank-account strong {
            color: #1e90ff !important;
        }
        
        @media (max-width: 768px) {
            .recharge-container {
                margin: 10px;
                padding: 20px;
            }
            
            .amount-options,
            .bank-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="home-page">
    <!-- Recharge Overlay - Form nổi trên nền trang chính -->
    <div class="auth-overlay">
        <div class="recharge-container">
            <div class="recharge-header">
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Quay lại Dashboard
                </a>
                <div class="recharge-logo">
                    <img src="assets/images/logo.png" alt="Logo" class="logo-img">
                    <h1 class="f-utm_nyala t-upper">Con Đường Tơ Lụa</h1>
                </div>
                <h2 class="f-cambria">Nạp Thẻ Silk</h2>
                <p class="f-calibri">Nạp Silk thông qua chuyển khoản ngân hàng</p>
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

            <!-- Recharge Form -->
            <form method="POST" class="recharge-form" id="rechargeForm">
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
                    <label>Chọn Ngân Hàng</label>
                    <div class="bank-options">
                        <div class="bank-btn" data-bank="vietcombank">
                            <div class="bank-icon" style="background: #1e90ff; color: white;">VCB</div>
                            <span>Vietcombank</span>
                        </div>
                        <div class="bank-btn" data-bank="vietinbank">
                            <div class="bank-icon" style="background: #00bfff; color: white;">VTB</div>
                            <span>VietinBank</span>
                        </div>
                        <div class="bank-btn" data-bank="agribank">
                            <div class="bank-icon" style="background: #4682b4; color: white;">AGB</div>
                            <span>Agribank</span>
                        </div>
                        <div class="bank-btn" data-bank="bidv">
                            <div class="bank-icon" style="background: #1e90ff; color: white;">BIDV</div>
                            <span>BIDV</span>
                        </div>
                        <div class="bank-btn" data-bank="techcombank">
                            <div class="bank-icon" style="background: #00bfff; color: white;">TCB</div>
                            <span>Techcombank</span>
                        </div>
                        <div class="bank-btn" data-bank="mbbank">
                            <div class="bank-icon" style="background: #4682b4; color: white;">MBB</div>
                            <span>MB Bank</span>
                        </div>
                    </div>
                    <input type="hidden" id="bank_code" name="bank_code" required>
                </div>

                <div class="form-group">
                    <label for="transaction_id">Mã Giao Dịch</label>
                    <input type="text" id="transaction_id" name="transaction_id" class="form-control" 
                           placeholder="Nhập mã giao dịch từ ngân hàng..." required>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-credit-card"></i> Nạp Thẻ Ngay
                </button>
            </form>

            <!-- Payment Information -->
            <div class="payment-info">
                <h4><i class="fas fa-info-circle"></i> Thông Tin Thanh Toán</h4>
                <p><strong>Tỷ lệ quy đổi:</strong> 1 VNĐ = 1 Silk</p>
                <p><strong>Thời gian xử lý:</strong> 5-15 phút sau khi chuyển khoản</p>
                <p><strong>Lưu ý:</strong> Vui lòng chuyển khoản chính xác số tiền và ghi rõ mã giao dịch</p>
                
                <div class="bank-account">
                    <p><strong>Vietcombank:</strong> 1234567890 - NGUYEN VAN A</p>
                    <p><strong>VietinBank:</strong> 0987654321 - NGUYEN VAN A</p>
                    <p><strong>Agribank:</strong> 1122334455 - NGUYEN VAN A</p>
                </div>
            </div>

            <div class="recharge-footer" style="text-align: center; margin-top: 30px;">
                    <a href="payment.php" class="btn-secondary">
                        <i class="fas fa-credit-card"></i> Thanh Toán Trực Tuyến
                    </a>
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-home"></i> Về Dashboard
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
            
            // Bank selection
            $('.bank-btn').on('click', function() {
                $('.bank-btn').removeClass('active');
                $(this).addClass('active');
                $('#bank_code').val($(this).data('bank'));
            });
            
            // Form validation
            $('#rechargeForm').on('submit', function(e) {
                const amount = $('#amount').val();
                const bank = $('#bank_code').val();
                const transaction = $('#transaction_id').val();
                
                if (!amount || amount < 10000 || amount > 10000000) {
                    alert('Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ!');
                    e.preventDefault();
                    return false;
                }
                
                if (!bank) {
                    alert('Vui lòng chọn ngân hàng!');
                    e.preventDefault();
                    return false;
                }
                
                if (!transaction) {
                    alert('Vui lòng nhập mã giao dịch!');
                    e.preventDefault();
                    return false;
                }
                
                if (!confirm(`Xác nhận nạp ${parseInt(amount).toLocaleString()} VNĐ?\nBạn sẽ nhận được ${parseInt(amount).toLocaleString()} Silk.`)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
