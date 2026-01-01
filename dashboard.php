<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Player';
$email = $_SESSION['email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Get user stats
require_once 'connection_manager.php';

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
    <title>Trang Cá Nhân - Con Đường Tơ Lụa Mobile</title>
    
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
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                </a>
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

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3 class="section-title">
                    <i class="fas fa-bolt"></i> Thao Tác Nhanh
                </h3>
                <div class="action-grid">
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
                    
                    <a href="recharge.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="action-text">
                            <h3>Chuyển Khoản</h3>
                            <p>Nạp Silk qua ngân hàng</p>
                        </div>
                    </a>
                    
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

    <script>
        console.log('Dashboard loaded');
        console.log('User: <?php echo htmlspecialchars($username); ?>');
        console.log('Characters: <?php echo $userStats['characters']; ?>');
        console.log('Silk: <?php echo $userStats['silk']; ?>');
    </script>
</body>
</html>
