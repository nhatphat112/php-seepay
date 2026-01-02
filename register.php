<?php
session_start();
require_once 'connection_manager.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($email)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (strlen($username) < 4 || strlen($username) > 20) {
        $error = 'Tên tài khoản phải từ 4-20 ký tự!';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Tên tài khoản chỉ được chứa chữ cái, số và dấu gạch dưới!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } else {
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Check if username exists
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM TB_User WHERE StrUserID = ?");
            $stmt->execute([$username]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $error = 'Tên tài khoản đã tồn tại!';
            } else {
                // Check if email exists
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM TB_User WHERE Email = ?");
                $stmt->execute([$email]);
                $result = $stmt->fetch();
                
                if ($result['count'] > 0) {
                    $error = 'Email đã được sử dụng!';
                } else {
                    // Hash password (MD5 for Silkroad compatibility)
                    $hashedPassword = md5($password);
                    
                    // Insert new user
                    $stmt = $db->prepare("
                        INSERT INTO TB_User (StrUserID, password, Email, regtime, reg_ip) 
                        VALUES (?, ?, ?, GETDATE(), ?)
                    ");
                    
                    $regIp = $_SERVER['REMOTE_ADDR'] ?? '';
                    if ($stmt->execute([$username, $hashedPassword, $email, $regIp])) {
                        $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                        
                        // Log registration
                        try {
                            $logDb = ConnectionManager::getLogDB();
                            $logStmt = $logDb->prepare("
                                INSERT INTO _LogEventUser (UserJID, EventID, EventData, RegDate)
                                VALUES (?, 1, ?, GETDATE())
                            ");
                            $logStmt->execute([0, "Register: $username from IP: " . $_SERVER['REMOTE_ADDR']]);
                        } catch (Exception $e) {
                            // Log error but don't affect registration
                        }
                    } else {
                        $error = 'Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại!';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Lỗi kết nối database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Con Đường Tơ Lụa Mobile</title>
    
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
        /* Auth overlay - Form nổi trên nền trang chính */
        .auth-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
            overflow-y: auto;
        }
        
        .auth-box {
            width: 100%;
            max-width: 480px;
            background: rgba(20, 20, 30, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 215, 0, 0.2);
            animation: slideIn 0.4s ease-out;
            margin: 20px auto;
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
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
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
        
        .auth-logo {
            margin-bottom: 20px;
        }
        
        .logo-img {
            width: 70px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .auth-logo h1 {
            font-size: 22px;
            color: #ffd700;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .auth-header h2 {
            font-size: 26px;
            color: #fff;
            margin: 15px 0 10px;
        }
        
        .auth-header p {
            color: #999;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #51cf66;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group label i {
            color: #ffd700;
            margin-right: 5px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }
        
        .form-group input::placeholder {
            color: #666;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #888;
            font-size: 12px;
        }
        
        .password-input {
            position: relative;
            display: flex;
        }
        
        .password-input input {
            flex: 1;
            padding-right: 45px;
        }
        
        .toggle-password {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 45px;
            background: transparent;
            border: none;
            color: #888;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: #ffd700;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 13px;
        }
        
        .password-strength .weak {
            color: #ff6b6b;
        }
        
        .password-strength .medium {
            color: #ffd93d;
        }
        
        .password-strength .strong {
            color: #51cf66;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            margin-top: 3px;
            cursor: pointer;
            width: 16px;
            height: 16px;
            accent-color: #ffd700;
        }
        
        .checkbox-label span {
            color: #999;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .checkbox-label a {
            color: #ffd700;
            text-decoration: none;
        }
        
        .checkbox-label a:hover {
            text-decoration: underline;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-large {
            width: 100%;
            padding: 14px 30px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .success-actions {
            display: flex;
            gap: 10px;
            flex-direction: column;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .auth-footer p {
            color: #999;
            font-size: 14px;
        }
        
        .link-highlight {
            color: #ffd700;
            text-decoration: none;
            font-weight: 600;
        }
        
        .link-highlight:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .auth-box {
                padding: 30px 20px;
            }
        }
    </style>
    
    <!-- jQuery -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
</head>
<body class="home-page">
    <!-- Auth Overlay - Form nổi trên nền trang chính -->
    <div class="auth-overlay">
        <div class="auth-box">
            <div class="auth-header">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                </a>
                <div class="auth-logo">
                    <img src="assets/images/logo.png" alt="Logo" class="logo-img">
                    <h1 class="f-utm_nyala t-upper">Con Đường Tơ Lụa</h1>
                </div>
                <h2 class="f-cambria">Đăng Ký Tài Khoản</h2>
                <p class="f-calibri">Bắt đầu hành trình huyền thoại của bạn</p>
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
                <div class="success-actions">
                    <a href="login.php" class="btn-primary btn-large">
                        <i class="fas fa-sign-in-alt"></i> Đăng Nhập Ngay
                    </a>
                    <a href="index.php" class="btn-secondary btn-large">
                        <i class="fas fa-home"></i> Về Trang Chủ
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="auth-form f-calibri" id="registerForm">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Tên tài khoản
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Nhập tên tài khoản (4-20 ký tự)"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required
                            minlength="4"
                            maxlength="20"
                            pattern="[a-zA-Z0-9_]+"
                        >
                        <small>Chỉ được sử dụng chữ cái, số và dấu gạch dưới (_)</small>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Nhập địa chỉ email của bạn"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Mật khẩu
                        </label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)"
                                required
                                minlength="6"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Xác nhận mật khẩu
                        </label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Nhập lại mật khẩu của bạn"
                                required
                                minlength="6"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" required>
                            <span>Tôi đồng ý với <a href="#">Điều khoản dịch vụ</a> và <a href="#">Chính sách bảo mật</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary btn-large t-upper">
                        <i class="fas fa-user-plus"></i> Đăng Ký Ngay
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Đã có tài khoản? <a href="login.php" class="link-highlight">Đăng nhập ngay</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength indicator
        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('input', function() {
                const password = this.value;
                const strengthDiv = document.getElementById('passwordStrength');
                
                let strength = 0;
                let strengthText = '';
                let strengthClass = '';
                
                if (password.length >= 6) strength++;
                if (password.length >= 10) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z\d]/.test(password)) strength++;
                
                switch(strength) {
                    case 0:
                    case 1:
                        strengthText = 'Yếu';
                        strengthClass = 'weak';
                        break;
                    case 2:
                    case 3:
                        strengthText = 'Trung bình';
                        strengthClass = 'medium';
                        break;
                    case 4:
                    case 5:
                        strengthText = 'Mạnh';
                        strengthClass = 'strong';
                        break;
                }
                
                if (password.length > 0) {
                    strengthDiv.innerHTML = `<span class="${strengthClass}">Độ mạnh: ${strengthText}</span>`;
                } else {
                    strengthDiv.innerHTML = '';
                }
            });
        }

        // Form validation
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Mật khẩu xác nhận không khớp!');
                    return false;
                }
            });
        }
    </script>
</body>
</html>
