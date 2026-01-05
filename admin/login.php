<?php
session_start();
require_once __DIR__ . '/../connection_manager.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Nếu là admin thì redirect về CMS, không thì về dashboard
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: cms/index.php');
    } else {
        header('Location: ../dashboard.php');
    }
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
                
                // Log login
                try {
                    $logDb = ConnectionManager::getLogDB();
                    $logStmt = $logDb->prepare("
                        INSERT INTO _LogEventUser (UserJID, EventID, EventData, RegDate)
                        VALUES (?, 2, ?, GETDATE())
                    ");
                    $logStmt->execute([$user['JID'], "Admin Login: $username from IP: " . $_SERVER['REMOTE_ADDR']]);
                } catch (Exception $e) {
                    // Log error but don't affect login
                }
                
                // Redirect based on role
                if (($user['role'] ?? 'user') === 'admin') {
                    header('Location: cms/index.php');
                } else {
                    header('Location: ../dashboard.php');
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
    <title>Đăng Nhập Admin - Song Long Tranh Bá Mobile</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../images/favicon.ico"/>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background: #16213e;
            padding: 40px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            color: #e8c088;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #87ceeb;
            font-size: 0.9rem;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #e8c088;
            background: rgba(255, 255, 255, 0.12);
        }
        
        .btn {
            width: 100%;
            padding: 12px 30px;
            background: linear-gradient(135deg, #e8c088 0%, #d4a574 100%);
            color: #1a1a2e;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(232, 192, 136, 0.4);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #87ceeb;
            text-decoration: none;
            font-size: 14px;
            text-align: center;
            width: 100%;
        }
        
        .back-link:hover {
            color: #e8c088;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Login</h1>
            <p>Đăng nhập vào hệ thống quản trị</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Tên tài khoản
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Nhập tên tài khoản"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Mật khẩu
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Nhập mật khẩu"
                    required
                >
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Đăng Nhập
            </button>
        </form>

        <a href="../login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại trang đăng nhập thường
        </a>
    </div>
</body>
</html>

