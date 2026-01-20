<?php
session_start();
require_once 'connection_manager.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Hash password (MD5 for Silkroad compatibility)
            $hashedPassword = md5($password);
            
            // Check user credentials
            $stmt = $db->prepare("
                SELECT JID, StrUserID, Email, ISNULL(role, 'user') as role
                FROM TB_User 
                WHERE StrUserID = ? AND password = ?
            ");
            $stmt->execute([$username, $hashedPassword]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Set session
                $_SESSION['user_id'] = $user['JID'];
                $_SESSION['username'] = $user['StrUserID'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['login_time'] = time();
                
                // Check and update season (auto transition if needed)
                try {
                    require_once 'includes/lucky_wheel_helper.php';
                    checkAndUpdateSeason();
                } catch (Exception $e) {
                    // Log error but don't affect login
                    error_log("Error checking season on login: " . $e->getMessage());
                }
                
                // Log login
                try {
                    $logDb = ConnectionManager::getLogDB();
                    $logStmt = $logDb->prepare("
                        INSERT INTO _LogEventUser (UserJID, EventID, EventData, RegDate)
                        VALUES (?, 2, ?, GETDATE())
                    ");
                    $logStmt->execute([$user['JID'], "Login: $username from IP: " . $_SERVER['REMOTE_ADDR']]);
                } catch (Exception $e) {
                    // Log error but don't affect login
                }
                
                // Redirect based on role
                if (($user['role'] ?? 'user') === 'admin') {
                    header('Location: admin/cms/index.php');
                } else {
                header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không chính xác!';
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
    <title>Đăng Nhập - Song Long Tranh Bá Mobile</title>
    
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
            overflow: hidden; /* Ẩn scrollbar */
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
            max-height: 90vh;
            overflow: hidden;
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
            border-radius: 50%;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            cursor: pointer;
            width: 16px;
            height: 16px;
            accent-color: #ffd700;
        }
        
        .checkbox-label span {
            color: #999;
            font-size: 14px;
        }
        
        .forgot-password {
            color: #ffd700;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .forgot-password:hover {
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
        
        .btn-social {
            flex: 1;
            padding: 12px 20px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-social:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        
        .divider span {
            color: #666;
            font-size: 13px;
            background: transparent;
            padding: 0 10px;
        }
        
        .social-login {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
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
            
            .social-login {
                flex-direction: column;
            }
            
            .checkbox-group {
                flex-direction: column;
                align-items: flex-start;
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
                        <h1 class="f-utm_nyala t-upper">Song Long Tranh Bá</h1>
                    </div>
                    <h2 class="f-cambria">Đăng Nhập</h2>
                    <p class="f-calibri">Chào mừng trở lại, chiến binh!</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form f-calibri">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Tên tài khoản
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Nhập tên tài khoản của bạn"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required
                            autofocus
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
                                placeholder="Nhập mật khẩu của bạn"
                                required
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group checkbox-group remember-row">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="#" class="forgot-password">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="btn-primary btn-large t-upper">
                        <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                    </button>
                </form>

                <!-- <div class="divider">
                    <span>hoặc đăng nhập với</span>
                </div>

                <div class="social-login">
                    <button class="btn btn-social btn-facebook" onclick="alert('Tính năng đang phát triển')">
                        <i class="fab fa-facebook"></i> Facebook
                    </button>
                    <button class="btn btn-social btn-google" onclick="alert('Tính năng đang phát triển')">
                        <i class="fab fa-google"></i> Google
                    </button>
                </div> -->

            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="register.php" class="link-highlight">Đăng ký ngay</a></p>
            </div>
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
    </script>
</body>
</html>
