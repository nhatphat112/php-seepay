<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/auth_helper.php';
require_once 'connection_manager.php';

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get current Silk amount
$silk = 0;
try {
    $db = ConnectionManager::getAccountDB();
    $stmt = $db->prepare("SELECT silk_own FROM SK_Silk WHERE JID = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $silk = intval($result['silk_own']);
    }
} catch (Exception $e) {
    // Continue with silk = 0 if error
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vòng Quay May Mắn - Song Long Tranh Bá Mobile</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico"/>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/vendor.css" />
    <link rel="stylesheet" href="assets/css/main1bce.css?v=6" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/font-override.css" />
    <link rel="stylesheet" href="css/auth-enhanced.css" />
    
    <style>
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
        
        .dashboard-wrapper {
            display: flex;
            position: relative;
            min-height: 100vh;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(15px);
        }
        
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
        
        .lucky-wheel-container {
            flex: 1;
            margin-left: 260px;
            width: calc(100% - 260px);
            background-image: url('lucky-spin-background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            padding: 40px;
            min-height: 100vh;
            box-sizing: border-box;
            position: relative;
        }
        
        .lucky-wheel-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 0;
        }
        
        .lucky-wheel-container > * {
            position: relative;
            z-index: 1;
        }
        
        .lucky-wheel-header {
            text-align: center;
            margin-bottom: 30px;
            padding-top: 20px;
        }
        
        .lucky-wheel-header h1 {
            font-size: 48px;
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 10px 0;
            text-shadow: 0 2px 10px rgba(255, 200, 0, 0.3);
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        
        .silk-balance {
            display: inline-block;
            background: rgba(255, 215, 0, 0.1);
            border: 2px solid #ffd700;
            padding: 10px 20px;
            border-radius: 25px;
            color: #ffd700;
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
            box-shadow: 0 2px 10px rgba(255, 200, 0, 0.3);
        }
        
        .spin-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .spin-btn {
            padding: 12px 30px;
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            border: none;
            border-radius: 8px;
            color: hsl(0, 40%, 15%);
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
            box-shadow: 0 4px 15px rgba(255, 200, 0, 0.4);
        }
        
        .spin-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 200, 0, 0.5);
            background: linear-gradient(180deg, #ffed4e, #ffd700);
        }
        
        .spin-btn.active {
            background: linear-gradient(180deg, #ffed4e, #ffd700);
        }
        
        .multi-spin-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .spin-select {
            background: hsl(0, 30%, 25%);
            color: #ffd700;
            border: 2px solid #ffd700;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .spin-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .spin-status {
            text-align: center;
            color: #ffd700;
            font-size: 18px;
            margin: 20px 0;
            min-height: 30px;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(255, 200, 0, 0.5);
        }
        
        .wheel-section {
            display: flex;
            gap: 30px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .wheel-main {
            flex: 1;
            min-width: 500px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .wheel-container {
            position: relative;
            width: 500px;
            height: 500px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .wheel-wrapper {
            position: relative;
            width: 500px;
            height: 500px;
        }
        
        #wheelCanvas {
            border-radius: 50%;
            box-shadow: 
                0 0 0 8px #ffd700,
                0 0 0 12px hsl(0, 85%, 45%),
                0 0 30px rgba(255, 200, 0, 0.5);
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
            background: transparent;
        }
        
        .wheel-pointer {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 50px;
            color: #ffd700;
            z-index: 10;
            filter: drop-shadow(0 2px 5px rgba(0,0,0,0.8));
            text-shadow: 0 0 10px rgba(255, 200, 0, 0.8);
        }
        
        .wheel-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 5;
            box-shadow: 0 0 20px rgba(255, 200, 0, 0.5);
            border: 3px solid hsl(0, 40%, 15%);
        }
        
        .wheel-center-text {
            color: hsl(0, 40%, 15%);
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            line-height: 1.2;
        }
        
        .info-panels {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .info-panel {
            flex: 1;
            min-width: 300px;
            background: linear-gradient(180deg, hsl(0, 30%, 25%), hsl(0, 40%, 15%));
            border: 2px solid #ffd700;
            border-radius: 12px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .info-panel h3 {
            color: #ffd700;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            text-align: center;
            border-bottom: 2px solid rgba(255, 200, 0, 0.3);
            padding-bottom: 10px;
        }
        
        .won-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border-left: 3px solid #ffd700;
        }
        
        .won-item-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #9370db;
        }
        
        .won-item-name {
            flex: 1;
            color: #fff;
        }
        
        .won-item-time {
            color: #87ceeb;
            font-size: 14px;
        }
        
        .claim-btn {
            padding: 5px 15px;
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            color: hsl(0, 40%, 15%);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(255, 200, 0, 0.3);
        }
        
        .claim-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(255, 200, 0, 0.5);
        }
        
        .claim-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            margin-bottom: 10px;
            background: rgba(26, 26, 46, 0.5);
            border-radius: 8px;
        }
        
        .leaderboard-rank {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: #fff;
        }
        
        .leaderboard-rank.rank-1 {
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            color: hsl(0, 40%, 15%);
        }
        
        .leaderboard-rank.rank-2 {
            background: #4682b4;
        }
        
        .leaderboard-rank.rank-3 {
            background: #ff8c00;
        }
        
        .leaderboard-rank.rank-other {
            background: #696969;
        }
        
        .leaderboard-info {
            flex: 1;
        }
        
        .leaderboard-name {
            color: #fff;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .leaderboard-item-name {
            color: #ffd700;
            font-size: 14px;
        }
        
        .leaderboard-count {
            color: #87ceeb;
            font-weight: bold;
        }
        
        /* Results Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }
        
        .modal-content {
            background: linear-gradient(180deg, hsl(0, 30%, 25%), hsl(0, 40%, 15%));
            margin: 50px auto;
            padding: 0;
            border: 4px solid #ffd700;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 0 50px rgba(255, 200, 0, 0.5);
        }
        
        .modal-header {
            background: rgba(0, 0, 0, 0.5);
            padding: 25px;
            border-bottom: 2px solid #ffd700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 11px 11px 0 0;
        }
        
        .modal-header h2 {
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .modal-header .close {
            color: #87ceeb;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
            line-height: 1;
        }
        
        .modal-header .close:hover {
            color: #e8c088;
        }
        
        .modal-body {
            padding: 25px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .modal-subtitle {
            color: #fff;
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .result-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 10px;
            background: rgba(26, 26, 46, 0.6);
            border-radius: 8px;
            border: 2px solid rgba(232, 192, 136, 0.3);
        }
        
        .result-item-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }
        
        .result-item-dot.red { background: #ff0000; }
        .result-item-dot.orange { background: #ff8c00; }
        .result-item-dot.green { background: #00ff00; }
        .result-item-dot.yellow { background: #ffff00; }
        .result-item-dot.purple { background: #9370db; }
        
        .result-item-name {
            flex: 1;
            color: #fff;
            font-weight: bold;
            font-size: 16px;
        }
        
        .result-item-name.rare {
            color: #ffd700;
            text-shadow: 0 0 10px rgba(255, 200, 0, 0.5);
        }
        
        .modal-footer {
            padding: 20px 25px;
            text-align: center;
            border-top: 2px solid rgba(232, 192, 136, 0.3);
            background: rgba(26, 26, 46, 0.8);
            border-radius: 0 0 11px 11px;
        }
        
        .modal-close-btn {
            padding: 12px 40px;
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            color: hsl(0, 40%, 15%);
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 200, 0, 0.4);
        }
        
        .modal-close-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 200, 0, 0.5);
        }
        
        /* Confetti */
        #confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 999;
        }
        
        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            animation: confettiFall 3s ease-out forwards;
        }
        
        @keyframes confettiFall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        .loading {
            text-align: center;
            color: #87ceeb;
            padding: 20px;
        }
        
        .empty-state {
            text-align: center;
            color: #87ceeb;
            padding: 30px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .dashboard-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .dashboard-sidebar.open {
                transform: translateX(0);
            }
            
            .lucky-wheel-container {
                margin-left: 0;
                width: 100%;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .wheel-container {
                width: 100%;
                max-width: 400px;
            }
            
            .wheel-wrapper {
                width: 100%;
                max-width: 400px;
            }
            
            #wheelCanvas {
                width: 100%;
                height: auto;
                max-width: 400px;
                max-height: 400px;
            }
            
            .spin-buttons {
                flex-direction: column;
            }
            
            .spin-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="home-page">
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-gamepad"></i> Game Menu</h1>
                <p>Song Long Tranh Bá</p>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Trang Chủ</a></li>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="payment.php"><i class="fas fa-coins"></i> Nạp Tiền</a></li>
                <li><a href="tichnap.php"><i class="fas fa-gift"></i> Nạp Tích Lũy</a></li>
                <li><a href="lucky_wheel.php" class="active"><i class="fas fa-dharmachakra"></i> Vòng Quay May Mắn</a></li>
                <li><a href="ranking.php"><i class="fas fa-trophy"></i> Bảng Xếp Hạng</a></li>
                <li><a href="transaction_history.php"><i class="fas fa-history"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <div class="lucky-wheel-container">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="lucky-wheel-header">
                <h1>VÒNG QUAY MAY MẮN</h1>
                <div class="silk-balance">
                    <i class="fas fa-coins"></i> Silk: <span id="silkBalance"><?php echo number_format($silk); ?></span>
                </div>
            </div>
            
            <!-- Spin Buttons -->
            <div class="spin-buttons">
                <button class="spin-btn" onclick="spin(1)" id="spinBtn1">
                    🎯 Quay 1 lần
                </button>
                <div class="multi-spin-group">
                    <button class="spin-btn" onclick="spinMultiple()" id="btnSpinMulti">
                        🔄 Quay nhiều lần
                    </button>
                    <select id="spinCount" class="spin-select">
                        <?php for($i = 2; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> lần</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <!-- Spin Status -->
            <div class="spin-status" id="spinStatus"></div>
            
            <!-- Wheel Section -->
            <div class="wheel-section">
                <div class="wheel-main">
                    <div class="wheel-container">
                        <div class="wheel-pointer">▼</div>
                        <div class="wheel-wrapper">
                            <canvas id="wheelCanvas" width="500" height="500"></canvas>
                            <div class="wheel-center">
                                <div class="wheel-center-text">LUCKY<br>WHEEL</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Info Panels -->
                <div class="info-panels">
                    <!-- Pending Rewards -->
                    <div class="info-panel">
                        <h3><i class="fas fa-trophy"></i> Vật Phẩm Đã Trúng</h3>
                        <div id="pendingRewards">
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin"></i> Đang tải...
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Players -->
                    <div class="info-panel">
                        <h3><i class="fas fa-crown"></i> Top Người Chơi</h3>
                        <div id="topPlayers">
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin"></i> Đang tải...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confetti Container -->
    <div id="confetti"></div>
    
    <!-- Results Modal -->
    <div id="resultsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-trophy"></i>
                    KẾT QUẢ
                </h2>
                <span class="close" onclick="closeResultsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="modal-subtitle" id="modalSubtitle"></div>
                <div id="resultsList"></div>
            </div>
            <div class="modal-footer">
                <button class="modal-close-btn" onclick="closeResultsModal()">ĐÓNG</button>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let wheelItems = [];
        let isSpinning = false;
        let spinCost = 10;
        let currentSilk = <?php echo $silk; ?>;
        
        // Load initial data
        $(document).ready(function() {
            loadConfig();
            loadItems();
            loadPendingRewards();
            loadTopPlayers();
        });
        
        // Load config
        function loadConfig() {
            $.ajax({
                url: '/api/lucky_wheel/get_config.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        spinCost = response.data.SpinCost || 10;
                        if (!response.data.FeatureEnabled) {
                            alert('Tính năng vòng quay may mắn đang tạm thời tắt');
                        }
                    }
                }
            });
        }
        
        // Load wheel items
        function loadItems() {
            $.ajax({
                url: '/api/lucky_wheel/get_items.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        wheelItems = response.data;
                        renderWheel();
                    }
                }
            });
        }
        
        // Wheel colors
        const WHEEL_COLORS = [
            "hsl(0, 85%, 55%)",    // Red
            "hsl(210, 90%, 55%)",  // Blue
            "hsl(140, 70%, 45%)",  // Green
            "hsl(45, 100%, 50%)",  // Gold
            "hsl(280, 80%, 55%)",  // Purple
            "hsl(25, 95%, 55%)",   // Orange
            "hsl(330, 85%, 60%)",  // Pink
            "hsl(180, 80%, 50%)",  // Cyan
            "hsl(50, 95%, 55%)",   // Yellow
            "hsl(260, 70%, 50%)",  // Violet
        ];
        
        let currentRotation = 0;
        
        // Render wheel with Canvas
        function renderWheel() {
            const canvas = document.getElementById('wheelCanvas');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 5;
            
            if (wheelItems.length === 0) {
                ctx.fillStyle = 'rgba(135, 206, 235, 0.5)';
                ctx.font = 'bold 20px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('Chưa có vật phẩm', centerX, centerY);
                return;
            }
            
            const segmentAngle = (2 * Math.PI) / wheelItems.length;
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            wheelItems.forEach((item, index) => {
                const startAngle = index * segmentAngle - Math.PI / 2;
                const endAngle = startAngle + segmentAngle;
                
                // Get color
                let color = WHEEL_COLORS[index % WHEEL_COLORS.length];
                if (item.is_rare) {
                    color = "hsl(280, 80%, 55%)"; // Purple for rare items
                }
                
                // Draw segment
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();
                ctx.fillStyle = color;
                ctx.fill();
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                // Draw text
                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(startAngle + segmentAngle / 2);
                ctx.textAlign = 'right';
                ctx.fillStyle = 'white';
                ctx.font = 'bold 13px Arial';
                ctx.shadowColor = 'rgba(0, 0, 0, 0.8)';
                ctx.shadowBlur = 3;
                
                const text = item.item_name.length > 15 
                    ? item.item_name.substring(0, 13) + '...' 
                    : item.item_name;
                ctx.fillText(text, radius - 15, 5);
                ctx.restore();
            });
        }
        
        // Spin function
        function spin(count) {
            if (isSpinning) {
                return;
            }
            
            const totalCost = spinCost * count;
            if (currentSilk < totalCost) {
                alert('Không đủ Silk! Bạn cần ' + totalCost + ' Silk để quay ' + count + ' lần.');
                return;
            }
            
            if (!confirm('Bạn có chắc muốn quay ' + count + ' lần? Tổng cộng: ' + totalCost + ' Silk')) {
                return;
            }
            
            isSpinning = true;
            disableSpinButtons(true);
            
            if (count === 1) {
                spinSingle();
            } else {
                spinMultiple(count);
            }
        }
        
        // Spin single
        function spinSingle() {
            $('#spinStatus').text('Đang quay...');
            
            $.ajax({
                url: '/api/lucky_wheel/spin.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ spin_count: 1 }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const result = response.data.results[0];
                        const itemIndex = wheelItems.findIndex(i => i.id === result.item_id);
                        
                        if (itemIndex !== -1) {
                            const segmentAngle = 360 / wheelItems.length;
                            const prizeAngle = itemIndex * segmentAngle + segmentAngle / 2;
                            const rotations = 5 + Math.random() * 3; // 5-8 vòng
                            const targetRotation = currentRotation + 360 * rotations + (360 - prizeAngle);
                            
                            const canvas = document.getElementById('wheelCanvas');
                            canvas.style.transform = `rotate(${targetRotation}deg)`;
                            currentRotation = targetRotation;
                            
                            setTimeout(function() {
                                updateSilkBalance(response.data.total_cost);
                                showResult([result]);
                                showConfetti();
                                loadPendingRewards();
                                isSpinning = false;
                                disableSpinButtons(false);
                                $('#spinStatus').text('🎉 Chúc mừng! Bạn đã trúng: ' + result.item_name);
                                
                                // Reset wheel after 2 seconds
                                setTimeout(function() {
                                    canvas.style.transform = 'rotate(0deg)';
                                    currentRotation = 0;
                                }, 2000);
                            }, 4000);
                        }
                    } else {
                        alert('Lỗi: ' + response.error);
                        isSpinning = false;
                        disableSpinButtons(false);
                        $('#spinStatus').text('');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert('Lỗi: ' + (response?.error || 'Có lỗi xảy ra'));
                    isSpinning = false;
                    disableSpinButtons(false);
                    $('#spinStatus').text('');
                }
            });
        }
        
        // Spin multiple
        function spinMultiple(count) {
            if (count === undefined) {
                count = parseInt($('#spinCount').val());
            }
            
            if (isSpinning) return;
            
            const totalCost = spinCost * count;
            if (currentSilk < totalCost) {
                alert('Không đủ Silk! Bạn cần ' + totalCost + ' Silk để quay ' + count + ' lần.');
                return;
            }
            
            if (!confirm('Bạn có chắc muốn quay ' + count + ' lần? Tổng cộng: ' + totalCost + ' Silk')) {
                return;
            }
            
            isSpinning = true;
            disableSpinButtons(true);
            
            $('#spinStatus').text('Đang quay lượt 0/' + count);
            
            // Symbolic animation
            const canvas = document.getElementById('wheelCanvas');
            let rotation = 0;
            const animateInterval = setInterval(function() {
                rotation += 30;
                canvas.style.transform = `rotate(${rotation}deg)`;
            }, 50);
            
            $.ajax({
                url: '/api/lucky_wheel/spin.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ spin_count: count }),
                dataType: 'json',
                success: function(response) {
                    clearInterval(animateInterval);
                    
                    if (response.success) {
                        updateSilkBalance(response.data.total_cost);
                        showResultsModal(response.data.results, count);
                        showConfetti();
                        loadPendingRewards();
                        isSpinning = false;
                        disableSpinButtons(false);
                        $('#spinStatus').text('Hoàn thành! Đã quay ' + count + ' lần');
                        
                        // Reset wheel
                        setTimeout(function() {
                            canvas.style.transform = 'rotate(0deg)';
                            currentRotation = 0;
                        }, 1000);
                    } else {
                        alert('Lỗi: ' + response.error);
                        isSpinning = false;
                        disableSpinButtons(false);
                        $('#spinStatus').text('');
                    }
                },
                error: function(xhr) {
                    clearInterval(animateInterval);
                    const response = xhr.responseJSON;
                    alert('Lỗi: ' + (response?.error || 'Có lỗi xảy ra'));
                    isSpinning = false;
                    disableSpinButtons(false);
                    $('#spinStatus').text('');
                }
            });
        }
        
        // Show single result
        function showResult(results) {
            // Could show a notification here
            console.log('Won items:', results);
        }
        
        // Show results modal
        function showResultsModal(results, totalSpins) {
            const modal = $('#resultsModal');
            const modalSubtitle = $('#modalSubtitle');
            const resultsList = $('#resultsList');
            
            modalSubtitle.text('Kết quả quay ' + totalSpins + ' lần');
            resultsList.empty();
            
            const colors = ['red', 'orange', 'green', 'yellow', 'purple'];
            
            // Count prizes
            const counts = {};
            results.forEach(prize => {
                const name = prize.item_name;
                counts[name] = (counts[name] || 0) + 1;
            });
            
            // Display unique prizes with counts
            let index = 0;
            for (const [name, count] of Object.entries(counts)) {
                const result = results.find(r => r.item_name === name);
                const color = colors[index % colors.length];
                const isRare = result && result.is_rare;
                
                const item = $('<div>')
                    .addClass('result-item')
                    .html(
                        '<div class="result-item-dot ' + color + '"></div>' +
                        '<div class="result-item-name' + (isRare ? ' rare' : '') + '">' + 
                        name + (count > 1 ? ' x' + count : '') + 
                        '</div>'
                    );
                resultsList.append(item);
                index++;
            }
            
            modal.show();
        }
        
        // Close results modal
        function closeResultsModal() {
            $('#resultsModal').hide();
        }
        
        // Load pending rewards
        function loadPendingRewards() {
            $.ajax({
                url: '/api/lucky_wheel/get_rewards.php?status=pending',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    const container = $('#pendingRewards');
                    container.empty();
                    
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function(reward) {
                            const wonDate = new Date(reward.won_date);
                            const timeStr = wonDate.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                            
                            const item = $('<div>')
                                .addClass('won-item')
                                .html(
                                    '<div class="won-item-dot"></div>' +
                                    '<div class="won-item-name">' + reward.item_name + '</div>' +
                                    '<div class="won-item-time">' + timeStr + '</div>' +
                                    '<button class="claim-btn" onclick="claimReward(' + reward.id + ')">Nhận</button>'
                                );
                            container.append(item);
                        });
                    } else {
                        container.html('<div class="empty-state">Chưa có vật phẩm nào</div>');
                    }
                },
                error: function() {
                    $('#pendingRewards').html('<div class="empty-state">Lỗi tải dữ liệu</div>');
                }
            });
        }
        
        // Claim reward
        function claimReward(rewardId) {
            if (!confirm('Xác nhận nhận phần thưởng này?')) {
                return;
            }
            
            $.ajax({
                url: '/api/lucky_wheel/claim_reward.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ reward_id: rewardId }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Đã nhận phần thưởng thành công!');
                        loadPendingRewards();
                    } else {
                        alert('Lỗi: ' + response.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert('Lỗi: ' + (response?.error || 'Có lỗi xảy ra'));
                }
            });
        }
        
        // Load top players
        function loadTopPlayers() {
            $.ajax({
                url: '/api/lucky_wheel/get_recent_rare_wins.php?limit=10',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    const container = $('#topPlayers');
                    container.empty();
                    
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function(player, index) {
                            const rank = index + 1;
                            const rankClass = rank === 1 ? 'rank-1' : (rank === 2 ? 'rank-2' : (rank === 3 ? 'rank-3' : 'rank-other'));
                            
                            const item = $('<div>')
                                .addClass('leaderboard-item')
                                .html(
                                    '<div class="leaderboard-rank ' + rankClass + '">' + rank + '</div>' +
                                    '<div class="leaderboard-info">' +
                                    '<div class="leaderboard-name">' + player.username + '</div>' +
                                    '<div class="leaderboard-item-name">' + player.item_name + '</div>' +
                                    '</div>'
                                );
                            container.append(item);
                        });
                    } else {
                        container.html('<div class="empty-state">Chưa có dữ liệu</div>');
                    }
                },
                error: function() {
                    $('#topPlayers').html('<div class="empty-state">Lỗi tải dữ liệu</div>');
                }
            });
        }
        
        // Update silk balance
        function updateSilkBalance(deducted) {
            currentSilk -= deducted;
            $('#silkBalance').text(number_format(currentSilk));
        }
        
        // Disable/enable spin buttons
        function disableSpinButtons(disable) {
            $('.spin-btn').prop('disabled', disable);
            $('#spinCount').prop('disabled', disable);
        }
        
        // Show confetti
        function showConfetti() {
            const container = document.getElementById('confetti');
            container.innerHTML = '';
            
            const colors = ['#ff0', '#f00', '#0f0', '#00f', '#f0f', '#0ff', '#ffd700'];
            
            for (let i = 0; i < 50; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti-piece';
                piece.style.left = Math.random() * 100 + '%';
                piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                piece.style.animationDelay = Math.random() * 0.5 + 's';
                container.appendChild(piece);
            }
            
            setTimeout(() => container.innerHTML = '', 3000);
        }
        
        // Toggle sidebar (mobile)
        function toggleSidebar() {
            $('#sidebar').toggleClass('open');
        }
        
        // Number format
        function number_format(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('resultsModal');
            if (event.target === modal) {
                closeResultsModal();
            }
        }
    </script>
</body>
</html>
