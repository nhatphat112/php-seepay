<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/auth_helper.php';

$username = $_SESSION['username'] ?? 'Player';
$email = $_SESSION['email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = getUserRole();

// Get user stats
require_once 'connection_manager.php';

// Handle password change
$passwordChangeError = '';
$passwordChangeSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordChangeError = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (strlen($newPassword) < 6) {
        $passwordChangeError = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordChangeError = 'Mật khẩu xác nhận không khớp!';
    } else {
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Verify old password
            $hashedOldPassword = md5($oldPassword);
            $stmt = $db->prepare("SELECT JID FROM TB_User WHERE JID = ? AND password = ?");
            $stmt->execute([$user_id, $hashedOldPassword]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $passwordChangeError = 'Mật khẩu cũ không chính xác!';
            } else {
                // Update password
                $hashedNewPassword = md5($newPassword);
                $stmt = $db->prepare("UPDATE TB_User SET password = ? WHERE JID = ?");
                $stmt->execute([$hashedNewPassword, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $passwordChangeSuccess = 'Đổi mật khẩu thành công!';
                    
                    // Log password change
                    try {
                        $logDb = ConnectionManager::getLogDB();
                        $logStmt = $logDb->prepare("
                            INSERT INTO _LogEventUser (UserJID, EventID, EventData, RegDate)
                            VALUES (?, 3, ?, GETDATE())
                        ");
                        $logStmt->execute([$user_id, "Password changed from IP: " . $_SERVER['REMOTE_ADDR']]);
                    } catch (Exception $e) {
                        // Log error but don't affect password change
                    }
                } else {
                    $passwordChangeError = 'Không thể cập nhật mật khẩu. Vui lòng thử lại!';
                }
            }
        } catch (Exception $e) {
            $passwordChangeError = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

$userStats = [
    'level' => 1,
    'silk' => 0,
    'characters' => 0,
    'playtime' => 0
];

$characterList = [];

try {
    // Lấy số Silk (từ web_tet)
    $dbAccount = ConnectionManager::getAccountDB();
    $stmtSilk = $dbAccount->prepare("SELECT silk_own FROM SK_Silk WHERE JID = ?");
    $stmtSilk->execute([$user_id]);
    $silkResult = $stmtSilk->fetch();
    $userStats['silk'] = $silkResult['silk_own'] ?? 0;
    
    // Lấy danh sách nhân vật (từ web_tet)
    $dbShard = ConnectionManager::getShardDB();
    
    // Đếm số nhân vật
    $stmtCount = $dbShard->prepare("
        SELECT COUNT(*) as count 
        FROM _Char c
        INNER JOIN _User u ON c.CharID = u.CharID
        WHERE u.UserJID = ?
    ");
    $stmtCount->execute([$user_id]);
    $countResult = $stmtCount->fetch();
    $userStats['characters'] = $countResult['count'] ?? 0;
    
    // Lấy thông tin chi tiết nhân vật
    if ($userStats['characters'] > 0) {
        $stmtChars = $dbShard->prepare("
            SELECT 
                c.CharName16,
                c.CurLevel,
                c.RefObjID,
                c.RemainGold,
                c.ExpOffset
            FROM _Char c
            INNER JOIN _User u ON c.CharID = u.CharID
            WHERE u.UserJID = ?
            ORDER BY c.CurLevel DESC, c.ExpOffset DESC
        ");
        $stmtChars->execute([$user_id]);
        $characterList = $stmtChars->fetchAll();
        
        // Lấy level cao nhất
        if (!empty($characterList)) {
            $userStats['level'] = $characterList[0]['CurLevel'] ?? 1;
        }
    }
} catch (Exception $e) {
    // Use default values if error
    error_log("Dashboard error: " . $e->getMessage());
}

// Function để lấy tên class từ RefObjID
function getClassName($refObjID) {
    // Silkroad class mapping (simplified)
    $classes = [
        1907 => 'Chiến Binh (CH)',
        1908 => 'Chiến Binh (EU)',
        14875 => 'Pháp Sư (CH)',
        14876 => 'Pháp Sư (EU)',
        14877 => 'Thầy Phù (CH)',
        14878 => 'Thánh Sư (EU)',
        14879 => 'Cung Thủ (CH)',
        14880 => 'Cung Thủ (EU)',
        14881 => 'Kiếm Sĩ (CH)',
        14882 => 'Kiếm Sĩ (EU)',
    ];
    
    // Get base class
    if (isset($classes[$refObjID])) {
        return $classes[$refObjID];
    }
    
    // Default based on ID range
    if ($refObjID >= 1907 && $refObjID <= 1932) {
        return ($refObjID % 2 == 1) ? 'Chiến Binh (CH)' : 'Chiến Binh (EU)';
    } elseif ($refObjID >= 14875 && $refObjID <= 14900) {
        $type = ($refObjID - 14875) % 8;
        $region = ($refObjID % 2 == 1) ? 'CH' : 'EU';
        $classNames = ['Pháp Sư', 'Thầy Phù/Thánh Sư', 'Cung Thủ', 'Kiếm Sĩ'];
        return ($classNames[floor($type/2)] ?? 'Nhân Vật') . " ($region)";
    }
    
    return 'Nhân Vật';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Cá Nhân - Song Long Tranh Bá Mobile</title>
    
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
        /* Dashboard overlay - Nổi trên nền trang chính */
        .dashboard-overlay {
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
        
        .dashboard-container {
            width: 100%;
            max-width: 1000px;
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
        .dashboard-container::before {
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
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1e90ff !important; /* Viền xanh nước biển */
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #000;
            font-weight: bold;
        }
        
        .user-details h2 {
            font-size: 24px;
            color: #ffd700;
            margin: 0 0 5px 0;
        }
        
        .user-details p {
            font-size: 14px;
            color: #888;
            margin: 0;
        }
        
        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #ff6b6b;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(20, 30, 50, 0.8) !important; /* Nền xanh tối */
            border: 2px solid #4682b4 !important; /* Viền xanh steel blue */
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            background: rgba(30, 144, 255, 0.1) !important; /* Hover xanh */
            border-color: #1e90ff !important; /* Viền xanh nước biển khi hover */
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(30, 144, 255, 0.3);
        }
        
        .stat-card i {
            font-size: 32px;
            color: #1e90ff !important; /* Icon xanh nước biển */
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            display: block;
            font-size: 28px;
            font-weight: bold;
            color: #87ceeb !important; /* Text xanh nhạt */
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            display: block;
            font-size: 13px;
            color: #87ceeb !important; /* Label xanh nhạt */
            text-transform: uppercase;
        }
        
        .section-title {
            font-size: 20px;
            color: #1e90ff !important; /* Text xanh nước biển */
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #1e90ff !important; /* Viền xanh nước biển */
        }
        
        .section-title i {
            font-size: 22px;
        }
        
        /* Character List */
        .character-section {
            margin-bottom: 30px;
        }
        
        .character-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .character-card {
            background: rgba(20, 30, 50, 0.8) !important; /* Nền xanh tối */
            border: 2px solid #4682b4 !important; /* Viền xanh steel blue */
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            color: #87ceeb !important; /* Text xanh nhạt */
        }
        
        .character-card:hover {
            background: rgba(30, 144, 255, 0.1) !important; /* Hover xanh */
            border-color: #1e90ff !important; /* Viền xanh nước biển khi hover */
            color: #ffffff !important;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(30, 144, 255, 0.3);
        }
        
        .character-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .character-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 237, 78, 0.2) 100%);
            border: 2px solid rgba(255, 215, 0, 0.4);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ffd700;
        }
        
        .character-info h3 {
            font-size: 16px;
            color: #fff;
            margin: 0 0 5px 0;
        }
        
        .character-info .class-name {
            font-size: 12px;
            color: #888;
        }
        
        .character-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .char-stat {
            background: rgba(255, 255, 255, 0.03);
            padding: 10px;
            border-radius: 8px;
        }
        
        .char-stat .label {
            display: block;
            font-size: 11px;
            color: #888;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .char-stat .value {
            display: block;
            font-size: 15px;
            color: #fff;
            font-weight: 600;
        }
        
        .no-characters {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }
        
        .no-characters i {
            font-size: 48px;
            color: #444;
            margin-bottom: 15px;
        }
        
        .no-characters p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .create-char-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .create-char-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
        
        /* Quick Actions */
        .quick-actions {
            margin-bottom: 30px;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .action-card {
            background: rgba(20, 30, 50, 0.8) !important; /* Nền xanh tối */
            border: 2px solid #4682b4 !important; /* Viền xanh steel blue */
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #87ceeb !important; /* Text xanh nhạt */
        }
        
        .action-card:hover {
            background: rgba(30, 144, 255, 0.1) !important; /* Hover xanh */
            border-color: #1e90ff !important; /* Viền xanh nước biển khi hover */
            color: #ffffff !important;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(30, 144, 255, 0.3);
        }
        
        .action-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1e90ff 0%, #00bfff 100%) !important; /* Gradient xanh */
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ffffff !important; /* Text trắng */
        }
        
        .action-text h3 {
            font-size: 16px;
            color: #fff;
            margin: 0 0 5px 0;
        }
        
        .action-text p {
            font-size: 13px;
            color: #888;
            margin: 0;
        }
        
        .back-home {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-home a:hover {
            color: #ffd700;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 30px 20px;
            }
            
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .character-list {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <!-- jQuery -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
</head>
<body class="home-page">
    <!-- Dashboard Overlay - Nổi trên nền trang chính -->
    <div class="auth-overlay">
        <div class="dashboard-container">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h2><?php echo htmlspecialchars($username); ?></h2>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button onclick="document.getElementById('changePasswordModal').style.display='flex'" class="change-password-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: rgba(30, 144, 255, 0.2); border: 1px solid rgba(30, 144, 255, 0.4); color: #87ceeb; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.3s; cursor: pointer;">
                        <i class="fas fa-key"></i> Đổi Mật Khẩu
                    </button>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-user"></i>
                    <span class="value"><?php echo $userStats['characters']; ?></span>
                    <span class="label">Nhân Vật</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-level-up-alt"></i>
                    <span class="value"><?php echo $userStats['level']; ?></span>
                    <span class="label">Cấp Cao Nhất</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-gem"></i>
                    <span class="value"><?php echo number_format($userStats['silk']); ?></span>
                    <span class="label">Silk</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <span class="value"><?php echo $userStats['playtime']; ?>h</span>
                    <span class="label">Thời Gian Chơi</span>
                </div>
            </div>

            <!-- Character List -->
            <div class="character-section">
                <h3 class="section-title">
                    <i class="fas fa-users"></i> Nhân Vật Của Bạn
                </h3>
                
                <?php if (!empty($characterList)): ?>
                    <div class="character-list">
                        <?php foreach ($characterList as $char): ?>
                            <div class="character-card">
                                <div class="character-header">
                                    <div class="character-icon">
                                        <i class="fas fa-user-ninja"></i>
                                    </div>
                                    <div class="character-info">
                                        <h3><?php echo htmlspecialchars($char['CharName16']); ?></h3>
                                        <span class="class-name"><?php echo htmlspecialchars(getClassName($char['RefObjID'])); ?></span>
                                    </div>
                                </div>
                                <div class="character-stats">
                                    <div class="char-stat">
                                        <span class="label">Cấp Độ</span>
                                        <span class="value"><?php echo htmlspecialchars($char['CurLevel']); ?></span>
                                    </div>
                                    <div class="char-stat">
                                        <span class="label">Vàng</span>
                                        <span class="value"><?php echo number_format($char['RemainGold']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-characters">
                        <i class="fas fa-user-slash"></i>
                        <p>Bạn chưa có nhân vật nào</p>
                        <a href="download.php" class="create-char-btn">
                            <i class="fas fa-download"></i> Tải Game Để Tạo Nhân Vật
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Transaction History -->
            <div class="transaction-section">
                <h3 class="section-title">
                    <i class="fas fa-history"></i> Lịch Sử Giao Dịch
                </h3>
                
                <div class="transaction-filters">
                    <div class="filter-row">
                        <input 
                            type="text" 
                            id="orderCodeFilter" 
                            placeholder="Tìm theo Order Code..."
                            class="filter-input"
                        >
                        <select id="statusFilter" class="filter-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending">Đang chờ</option>
                            <option value="processing">Đang xử lý</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="failed">Thất bại</option>
                            <option value="expired">Hết hạn</option>
                        </select>
                        <button onclick="loadTransactions()" class="filter-btn">
                            <i class="fas fa-search"></i> Tìm Kiếm
                        </button>
                        <button onclick="resetTransactionFilters()" class="filter-btn filter-btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
                
                <div class="transaction-container">
                    <div class="loading-transactions" id="loadingTransactions" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                    <div class="transaction-table-wrapper">
                        <table class="transaction-table" id="transactionTable" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Order Code</th>
                                    <th>Số Tiền</th>
                                    <th>Silk</th>
                                    <th>Trạng Thái</th>
                                    <th>Phương Thức</th>
                                    <th>Ngày Tạo</th>
                                    <th>Ngày Hoàn Thành</th>
                                </tr>
                            </thead>
                            <tbody id="transactionBody">
                            </tbody>
                        </table>
                        <div id="noTransactions" style="display: none; text-align: center; padding: 40px; color: #87ceeb;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; color: #4682b4;"></i>
                            <p>Không có giao dịch nào</p>
                        </div>
                    </div>
                    <div class="transaction-pagination" id="transactionPagination" style="display: none;">
                        <button onclick="changeTransactionPage(-1)" id="prevTransactionBtn" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Trước
                        </button>
                        <span class="pagination-info">
                            Trang <input type="number" id="transactionPageInput" min="1" value="1" class="page-input"> / <span id="totalTransactionPages">1</span>
                        </span>
                        <button onclick="changeTransactionPage(1)" id="nextTransactionBtn" class="pagination-btn">
                            Sau <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3 class="section-title">
                    <i class="fas fa-bolt"></i> Thao Tác Nhanh
                </h3>
                <div class="action-grid">
                    <?php if (isAdmin()): ?>
                    <a href="admin/cms/index.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="action-text">
                            <h3>CMS Admin</h3>
                            <p>Quản lý nội dung hệ thống</p>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <a href="download.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="action-text">
                            <h3>Tải Game</h3>
                            <p>Tải client và công cụ hỗ trợ</p>
                        </div>
                    </a>
                    
                    <a href="payment.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="action-text">
                            <h3>Thanh Toán</h3>
                            <p>Nạp Silk trực tuyến</p>
                        </div>
                    </a>
                    
                    <!-- <a href="recharge.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="action-text">
                            <h3>Chuyển Khoản</h3>
                            <p>Nạp Silk qua ngân hàng</p>
                        </div>
                    </a> -->
                    
                    <a href="ranking.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="action-text">
                            <h3>Xếp Hạng</h3>
                            <p>Xem bảng xếp hạng server</p>
                        </div>
                    </a>
                    
                    <a href="#" class="action-card" onclick="alert('Tính năng đang phát triển!'); return false;">
                        <div class="action-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="action-text">
                            <h3>Hỗ Trợ</h3>
                            <p>Liên hệ hỗ trợ khách hàng</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-home"></i> Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal-overlay" style="display: <?php echo ($passwordChangeError || $passwordChangeSuccess) ? 'flex' : 'none'; ?>;">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Đổi Mật Khẩu</h3>
                <button onclick="closePasswordModal()" class="modal-close">&times;</button>
            </div>
            
            <?php if ($passwordChangeError): ?>
                <div class="alert alert-error" style="margin: 15px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($passwordChangeError); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($passwordChangeSuccess): ?>
                <div class="alert alert-success" style="margin: 15px; background: rgba(40, 167, 69, 0.15); border: 1px solid rgba(40, 167, 69, 0.3); color: #51cf66; animation: slideDown 0.3s ease-out;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($passwordChangeSuccess); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="password-form">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                    <label for="old_password">
                        <i class="fas fa-lock"></i> Mật khẩu cũ
                    </label>
                    <input 
                        type="password" 
                        id="old_password" 
                        name="old_password" 
                        placeholder="Nhập mật khẩu hiện tại"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-key"></i> Mật khẩu mới
                    </label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                        required
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-check"></i> Xác nhận mật khẩu mới
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Nhập lại mật khẩu mới"
                        required
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closePasswordModal()" class="btn-cancel">
                        Hủy
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Đổi Mật Khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .change-password-btn:hover {
            background: rgba(30, 144, 255, 0.3) !important;
            transform: translateY(-2px);
        }
        
        .modal-overlay {
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
            z-index: 10000;
            padding: 20px;
        }
        
        .modal-container {
            background: rgba(10, 20, 40, 0.95);
            border: 2px solid #1e90ff;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(30, 144, 255, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #1e90ff;
        }
        
        .modal-header h3 {
            font-size: 22px;
            color: #1e90ff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: transparent;
            border: none;
            color: #87ceeb;
            font-size: 32px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: #ff6b6b;
        }
        
        .password-form .form-group {
            margin-bottom: 20px;
        }
        
        .password-form label {
            display: block;
            color: #87ceeb;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .password-form label i {
            color: #1e90ff;
            margin-right: 5px;
        }
        
        .password-form input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s;
        }
        
        .password-form input:focus {
            outline: none;
            border-color: #1e90ff;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn-cancel,
        .btn-submit {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: #87ceeb;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #1e90ff 0%, #00bfff 100%);
            color: #fff;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 144, 255, 0.4);
        }
        
        .alert-success {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            padding: 15px;
            border-radius: 8px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(40, 167, 69, 0.95);
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
            min-width: 300px;
        }
        
        .toast-notification i {
            font-size: 20px;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Transaction History Styles */
        .transaction-section {
            margin-bottom: 30px;
        }
        
        .transaction-filters {
            background: rgba(20, 30, 50, 0.8);
            border: 2px solid #4682b4;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-input,
        .filter-select {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
        }
        
        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #1e90ff;
            background: rgba(255, 255, 255, 0.12);
        }
        
        .filter-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #1e90ff 0%, #00bfff 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 144, 255, 0.4);
        }
        
        .filter-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .transaction-container {
            background: rgba(20, 30, 50, 0.8);
            border: 2px solid #4682b4;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }
        
        .loading-transactions {
            text-align: center;
            padding: 40px;
            color: #87ceeb;
            font-size: 16px;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            color: #87ceeb;
        }
        
        .transaction-table thead {
            background: rgba(30, 144, 255, 0.1);
        }
        
        .transaction-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #1e90ff;
            border-bottom: 2px solid #1e90ff;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .transaction-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }
        
        .transaction-table tbody tr:hover {
            background: rgba(30, 144, 255, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #ffa500;
            color: #1a1a2e;
        }
        
        .status-processing {
            background: #1e90ff;
            color: #fff;
        }
        
        .status-completed {
            background: #4caf50;
            color: #fff;
        }
        
        .status-failed {
            background: #f44336;
            color: #fff;
        }
        
        .status-expired {
            background: #9e9e9e;
            color: #fff;
        }
        
        .transaction-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: rgba(20, 30, 50, 0.8);
            border: 2px solid #4682b4;
            border-radius: 12px;
        }
        
        .pagination-btn {
            padding: 8px 15px;
            background: rgba(30, 144, 255, 0.2);
            border: 1px solid #4682b4;
            color: #87ceeb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: rgba(30, 144, 255, 0.3);
            border-color: #1e90ff;
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-info {
            color: #87ceeb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .page-input {
            width: 50px;
            padding: 5px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 5px;
            color: #fff;
            text-align: center;
            font-size: 14px;
        }
        
        .page-input:focus {
            outline: none;
            border-color: #1e90ff;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .filter-input,
            .filter-select {
                width: 100%;
            }
            
            .transaction-table {
                font-size: 12px;
            }
            
            .transaction-table th,
            .transaction-table td {
                padding: 8px 5px;
            }
        }
    </style>

    <!-- Toast Notification -->
    <?php if ($passwordChangeSuccess): ?>
    <div id="successToast" class="toast-notification">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($passwordChangeSuccess); ?></span>
    </div>
    <?php endif; ?>

    <script>
        function closePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
            // Reset form
            document.querySelector('.password-form').reset();
            // Clear alerts after closing
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.remove();
                });
            }, 100);
        }
        
        // Auto close modal after success
        <?php if ($passwordChangeSuccess): ?>
        setTimeout(function() {
            closePasswordModal();
        }, 2000);
        
        // Auto hide toast notification
        setTimeout(function() {
            var toast = document.getElementById('successToast');
            if (toast) {
                toast.style.animation = 'slideInRight 0.3s ease-out reverse';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }
        }, 3000);
        <?php endif; ?>
        
        // Close modal when clicking outside
        document.getElementById('changePasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePasswordModal();
            }
        });
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePasswordModal();
            }
        });
        
        // Transaction History
        let currentTransactionPage = 1;
        let totalTransactionPages = 1;
        
        // Load transactions on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactions();
            
            // Allow Enter key in filter inputs
            const orderCodeFilter = document.getElementById('orderCodeFilter');
            if (orderCodeFilter) {
                orderCodeFilter.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        loadTransactions();
                    }
                });
            }
            
            // Allow Enter key in page input
            const transactionPageInput = document.getElementById('transactionPageInput');
            if (transactionPageInput) {
                transactionPageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const page = parseInt(this.value);
                        if (page >= 1 && page <= totalTransactionPages) {
                            currentTransactionPage = page;
                            loadTransactions();
                        }
                    }
                });
            }
        });
        
        // Load transactions
        async function loadTransactions(page = 1) {
            const loading = document.getElementById('loadingTransactions');
            const table = document.getElementById('transactionTable');
            const noData = document.getElementById('noTransactions');
            const pagination = document.getElementById('transactionPagination');
            
            if (!loading || !table || !noData || !pagination) {
                console.error('Transaction elements not found');
                return;
            }
            
            loading.style.display = 'block';
            table.style.display = 'none';
            noData.style.display = 'none';
            pagination.style.display = 'none';
            
            const params = new URLSearchParams({
                page: page,
                limit: 10
            });
            
            const orderCodeFilter = document.getElementById('orderCodeFilter');
            const statusFilter = document.getElementById('statusFilter');
            
            const orderCode = orderCodeFilter ? orderCodeFilter.value.trim() : '';
            const status = statusFilter ? statusFilter.value : '';
            
            if (orderCode) params.append('order_code', orderCode);
            if (status) params.append('status', status);
            
            try {
                const response = await fetch(`api/sepay/get_user_orders.php?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    const orders = result.orders || [];
                    totalTransactionPages = result.pagination?.total_pages || 1;
                    currentTransactionPage = result.pagination?.page || 1;
                    
                    if (orders.length > 0) {
                        displayTransactions(orders);
                        table.style.display = 'table';
                        updateTransactionPagination();
                        pagination.style.display = 'flex';
                    } else {
                        noData.style.display = 'block';
                        pagination.style.display = 'flex';
                        updateTransactionPagination();
                    }
                } else {
                    console.error('Error loading transactions:', result.error);
                    noData.style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
                noData.style.display = 'block';
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Display transactions
        function displayTransactions(orders) {
            const tbody = document.getElementById('transactionBody');
            if (!tbody) return;
            
            tbody.innerHTML = orders.map(order => {
                const statusClass = 'status-' + (order.Status || 'pending');
                const statusText = getStatusText(order.Status);
                const amount = parseFloat(order.Amount || 0).toLocaleString('vi-VN');
                const silk = parseInt(order.SilkAmount || 0).toLocaleString('vi-VN');
                const createdDate = formatDate(order.CreatedDate);
                const completedDate = order.CompletedDate ? formatDate(order.CompletedDate) : '-';
                const paymentMethod = getPaymentMethodText(order.PaymentMethod);
                
                return `
                    <tr>
                        <td><strong>${escapeHtml(order.OrderCode || '')}</strong></td>
                        <td>${amount} VNĐ</td>
                        <td>${silk} Silk</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${paymentMethod}</td>
                        <td>${createdDate}</td>
                        <td>${completedDate}</td>
                    </tr>
                `;
            }).join('');
        }
        
        // Get status text
        function getStatusText(status) {
            const statusMap = {
                'pending': 'Đang chờ',
                'processing': 'Đang xử lý',
                'completed': 'Hoàn thành',
                'failed': 'Thất bại',
                'expired': 'Hết hạn'
            };
            return statusMap[status] || status || 'Đang chờ';
        }
        
        // Get payment method text
        function getPaymentMethodText(method) {
            const methodMap = {
                'QR_CODE': 'QR Code',
                'BANK_TRANSFER': 'Chuyển khoản',
                'CARD': 'Thẻ cào'
            };
            return methodMap[method] || method || '-';
        }
        
        // Format date
        function formatDate(dateString) {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                return date.toLocaleString('vi-VN', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Update pagination
        function updateTransactionPagination() {
            const pageInput = document.getElementById('transactionPageInput');
            const totalPagesSpan = document.getElementById('totalTransactionPages');
            const prevBtn = document.getElementById('prevTransactionBtn');
            const nextBtn = document.getElementById('nextTransactionBtn');
            
            if (pageInput) pageInput.value = currentTransactionPage;
            if (totalPagesSpan) totalPagesSpan.textContent = totalTransactionPages;
            if (prevBtn) prevBtn.disabled = currentTransactionPage <= 1;
            if (nextBtn) nextBtn.disabled = currentTransactionPage >= totalTransactionPages;
        }
        
        // Change page
        function changeTransactionPage(direction) {
            const newPage = currentTransactionPage + direction;
            if (newPage >= 1 && newPage <= totalTransactionPages) {
                currentTransactionPage = newPage;
                loadTransactions(newPage);
            }
        }
        
        // Reset filters
        function resetTransactionFilters() {
            const orderCodeFilter = document.getElementById('orderCodeFilter');
            const statusFilter = document.getElementById('statusFilter');
            
            if (orderCodeFilter) orderCodeFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            currentTransactionPage = 1;
            loadTransactions(1);
        }
        
        console.log('Dashboard loaded');
        console.log('User: <?php echo htmlspecialchars($username); ?>');
        console.log('Characters: <?php echo $userStats['characters']; ?>');
        console.log('Silk: <?php echo $userStats['silk']; ?>');
    </script>
</body>
</html>
