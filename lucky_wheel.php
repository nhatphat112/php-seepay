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
            font-size: 52px;
            background: linear-gradient(180deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 10px 0;
            text-shadow: 0 0 20px rgba(255, 200, 0, 0.6), 0 0 40px rgba(255, 200, 0, 0.4);
            font-weight: 900;
            letter-spacing: 4px;
            text-transform: uppercase;
            animation: titleGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes titleGlow {
            0% {
                filter: drop-shadow(0 0 10px rgba(255, 200, 0, 0.5));
            }
            100% {
                filter: drop-shadow(0 0 25px rgba(255, 200, 0, 0.9));
            }
        }
        
        .silk-balance {
            display: inline-block;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 215, 0, 0.05));
            border: 2px solid #ffd700;
            padding: 12px 25px;
            border-radius: 30px;
            color: #ffd700;
            font-size: 20px;
            font-weight: bold;
            margin-top: 15px;
            box-shadow: 
                0 0 15px rgba(255, 200, 0, 0.4),
                0 4px 15px rgba(255, 200, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            text-shadow: 0 0 10px rgba(255, 200, 0, 0.6);
            backdrop-filter: blur(10px);
        }
        
        .spin-controls {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .spin-options {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .spin-option-btn {
            padding: 12px 30px;
            background: hsl(0, 30%, 25%);
            color: #87ceeb;
            border: 2px solid #87ceeb;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .spin-option-btn:hover:not(:disabled) {
            background: hsl(0, 30%, 30%);
            border-color: #ffd700;
            color: #ffd700;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 200, 0, 0.3);
        }
        
        .spin-option-btn.active {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
            color: hsl(0, 40%, 15%);
            border-color: #ffd700;
            box-shadow: 
                0 0 15px rgba(255, 200, 0, 0.5),
                0 4px 15px rgba(255, 200, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            font-weight: 900;
        }
        
        .spin-option-btn.active:hover:not(:disabled) {
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 50%, #ffed4e 100%);
            transform: translateY(-3px);
            box-shadow: 
                0 0 20px rgba(255, 200, 0, 0.6),
                0 6px 20px rgba(255, 200, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }
        
        .spin-option-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .spin-btn {
            padding: 18px 60px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
            border: none;
            border-radius: 12px;
            color: hsl(0, 40%, 15%);
            font-size: 22px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 220px;
            box-shadow: 
                0 0 20px rgba(255, 200, 0, 0.5),
                0 6px 20px rgba(255, 200, 0, 0.4),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
        }
        
        .spin-btn::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            transition: all 0.5s;
        }
        
        .spin-btn:hover:not(:disabled)::before {
            left: 100%;
        }
        
        .spin-btn:hover:not(:disabled) {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 
                0 0 30px rgba(255, 200, 0, 0.7),
                0 8px 30px rgba(255, 200, 0, 0.6),
                inset 0 2px 0 rgba(255, 255, 255, 0.4);
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 50%, #ffed4e 100%);
        }
        
        .spin-btn:active:not(:disabled) {
            transform: translateY(-1px);
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
            filter: drop-shadow(0 0 50px rgba(255, 200, 0, 0.4));
        }
        
        .wheel-wrapper {
            position: relative;
            width: 500px;
            height: 500px;
            max-width: 100%;
            max-height: 100%;
        }
        
        #wheelCanvas {
            border-radius: 50%;
            box-shadow: 
                0 0 0 10px #ffd700,
                0 0 0 14px hsl(0, 85%, 45%),
                0 0 0 18px #ffd700,
                0 0 50px rgba(255, 200, 0, 0.6),
                0 0 100px rgba(255, 200, 0, 0.4),
                inset 0 0 30px rgba(255, 200, 0, 0.2);
            transition: transform 5s cubic-bezier(0.17, 0.67, 0.12, 0.99);
            background: transparent;
            position: relative;
        }
        
        .wheel-pointer {
            position: absolute;
            top: -45px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 25px solid transparent;
            border-right: 25px solid transparent;
            border-top: 40px solid #ffd700;
            z-index: 10;
            filter: drop-shadow(0 0 15px rgba(255, 200, 0, 0.8));
            animation: pointerPulse 2s ease-in-out infinite;
        }
        
        .wheel-pointer::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -20px;
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-top: 30px solid rgba(255, 215, 0, 0.6);
            filter: blur(5px);
        }
        
        @keyframes pointerPulse {
            0%, 100% {
                transform: translateX(-50%) scale(1);
                filter: drop-shadow(0 0 15px rgba(255, 200, 0, 0.8));
            }
            50% {
                transform: translateX(-50%) scale(1.1);
                filter: drop-shadow(0 0 25px rgba(255, 200, 0, 1));
            }
        }
        
        .wheel-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 5;
            box-shadow: 
                0 0 30px rgba(255, 200, 0, 0.8),
                0 0 60px rgba(255, 200, 0, 0.5),
                inset 0 2px 10px rgba(255, 255, 255, 0.3),
                inset 0 -2px 10px rgba(0, 0, 0, 0.3);
            border: 4px solid hsl(0, 40%, 15%);
            padding: 15px;
            box-sizing: border-box;
            animation: centerGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes centerGlow {
            0% {
                box-shadow: 
                    0 0 30px rgba(255, 200, 0, 0.8),
                    0 0 60px rgba(255, 200, 0, 0.5),
                    inset 0 2px 10px rgba(255, 255, 255, 0.3),
                    inset 0 -2px 10px rgba(0, 0, 0, 0.3);
            }
            100% {
                box-shadow: 
                    0 0 40px rgba(255, 200, 0, 1),
                    0 0 80px rgba(255, 200, 0, 0.7),
                    inset 0 2px 10px rgba(255, 255, 255, 0.4),
                    inset 0 -2px 10px rgba(0, 0, 0, 0.3);
            }
        }
        
        .wheel-center img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
            filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.5));
        }
        
        .info-panels {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .info-panel {
            flex: 1;
            min-width: 300px;
            background: linear-gradient(135deg, hsl(0, 30%, 25%) 0%, hsl(0, 40%, 15%) 100%);
            border: 2px solid #ffd700;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 
                0 0 20px rgba(255, 200, 0, 0.3),
                0 8px 25px rgba(0,0,0,0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .rewards-panel {
            max-width: 100%;
            width: 100%;
        }
        
        .rewards-panel #pendingRewards,
        .rewards-panel #accumulatedRewards {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .rewards-panel #pendingRewards::-webkit-scrollbar,
        .rewards-panel #accumulatedRewards::-webkit-scrollbar {
            width: 8px;
        }
        
        .rewards-panel #pendingRewards::-webkit-scrollbar-track,
        .rewards-panel #accumulatedRewards::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }
        
        .rewards-panel #pendingRewards::-webkit-scrollbar-thumb,
        .rewards-panel #accumulatedRewards::-webkit-scrollbar-thumb {
            background: #ffd700;
            border-radius: 4px;
        }
        
        .rewards-panel #pendingRewards::-webkit-scrollbar-thumb:hover,
        .rewards-panel #accumulatedRewards::-webkit-scrollbar-thumb:hover {
            background: #ffed4e;
        }
        
        .total-spins-display {
            text-align: center;
            padding: 15px;
            background: rgba(255, 200, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 200, 0, 0.3);
        }
        
        .total-spins-label {
            color: #87ceeb;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .total-spins-value {
            color: #ffd700;
            font-size: 24px;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(255, 200, 0, 0.5);
        }
        
        .accumulated-reward-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, rgba(255, 200, 0, 0.1), rgba(255, 200, 0, 0.05));
            border: 1px solid rgba(255, 200, 0, 0.3);
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .accumulated-reward-item:hover {
            background: linear-gradient(135deg, rgba(255, 200, 0, 0.15), rgba(255, 200, 0, 0.1));
            border-color: rgba(255, 200, 0, 0.5);
            transform: translateX(5px);
        }
        
        .accumulated-reward-info {
            flex: 1;
        }
        
        .accumulated-reward-name {
            color: #ffd700;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .accumulated-reward-details {
            color: #87ceeb;
            font-size: 13px;
        }
        
        .accumulated-reward-progress {
            color: #e8c088;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .claim-accumulated-btn {
            padding: 10px 20px;
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            color: hsl(0, 40%, 15%);
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(255, 200, 0, 0.4);
            white-space: nowrap;
        }
        
        .claim-accumulated-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 200, 0, 0.5);
        }
        
        .claim-accumulated-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            gap: 15px;
            padding: 15px;
            margin-bottom: 12px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border-left: 3px solid #ffd700;
            transition: all 0.3s;
        }
        
        .won-item:hover {
            background: rgba(0, 0, 0, 0.5);
            border-left-color: #ffed4e;
            transform: translateX(5px);
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
            padding: 8px 20px;
            background: linear-gradient(180deg, #ffd700, #ffed4e);
            color: hsl(0, 40%, 15%);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(255, 200, 0, 0.3);
            white-space: nowrap;
        }
        
        .claim-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(255, 200, 0, 0.5);
            background: linear-gradient(180deg, #ffed4e, #ffd700);
        }
        
        .claim-btn:active:not(:disabled) {
            transform: scale(0.95);
        }
        
        .claim-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .claim-btn.claiming {
            opacity: 0.7;
            cursor: wait;
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
            background: linear-gradient(135deg, hsl(0, 30%, 25%) 0%, hsl(0, 40%, 15%) 100%);
            margin: 50px auto;
            padding: 0;
            border: 4px solid #ffd700;
            border-radius: 20px;
            width: 90%;
            max-width: 650px;
            box-shadow: 
                0 0 60px rgba(255, 200, 0, 0.6),
                0 0 100px rgba(255, 200, 0, 0.4),
                0 10px 40px rgba(0, 0, 0, 0.6);
            animation: modalAppear 0.4s ease-out;
            backdrop-filter: blur(15px);
        }
        
        @keyframes modalAppear {
            0% {
                transform: scale(0.8) translateY(-50px);
                opacity: 0;
            }
            100% {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
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
            padding: 18px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.8), rgba(20, 20, 35, 0.6));
            border-radius: 12px;
            border: 2px solid rgba(255, 200, 0, 0.4);
            box-shadow: 
                0 2px 10px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .result-item:hover {
            transform: translateX(5px);
            border-color: rgba(255, 200, 0, 0.6);
            box-shadow: 
                0 4px 15px rgba(255, 200, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        
        .result-item-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            box-shadow: 
                0 0 10px rgba(255, 255, 255, 0.3),
                inset 0 2px 5px rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .result-item-dot.red { 
            background: linear-gradient(135deg, #ff4444, #cc0000);
            box-shadow: 0 0 15px rgba(255, 68, 68, 0.6);
        }
        .result-item-dot.orange { 
            background: linear-gradient(135deg, #ffaa44, #ff8c00);
            box-shadow: 0 0 15px rgba(255, 170, 68, 0.6);
        }
        .result-item-dot.green { 
            background: linear-gradient(135deg, #44ff44, #00cc00);
            box-shadow: 0 0 15px rgba(68, 255, 68, 0.6);
        }
        .result-item-dot.yellow { 
            background: linear-gradient(135deg, #ffff44, #cccc00);
            box-shadow: 0 0 15px rgba(255, 255, 68, 0.6);
        }
        .result-item-dot.purple { 
            background: linear-gradient(135deg, #aa44ff, #9370db);
            box-shadow: 0 0 15px rgba(170, 68, 255, 0.6);
        }
        
        .result-item-name {
            flex: 1;
            color: #fff;
            font-weight: 700;
            font-size: 17px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        }
        
        .result-item-name.rare {
            color: #ffd700;
            text-shadow: 
                0 0 15px rgba(255, 200, 0, 0.8),
                0 2px 5px rgba(0, 0, 0, 0.5);
            animation: rareGlow 2s ease-in-out infinite alternate;
        }
        
        @keyframes rareGlow {
            0% {
                text-shadow: 
                    0 0 15px rgba(255, 200, 0, 0.8),
                    0 2px 5px rgba(0, 0, 0, 0.5);
            }
            100% {
                text-shadow: 
                    0 0 25px rgba(255, 200, 0, 1),
                    0 0 35px rgba(255, 200, 0, 0.6),
                    0 2px 5px rgba(0, 0, 0, 0.5);
            }
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
                padding: 20px 15px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .lucky-wheel-header h1 {
                font-size: 32px;
                letter-spacing: 2px;
            }
            
            .silk-balance {
                font-size: 16px;
                padding: 10px 18px;
            }
            
            .wheel-section {
                flex-direction: column;
                gap: 20px;
            }
            
            .wheel-main {
                min-width: 100%;
                width: 100%;
            }
            
            .wheel-container {
                width: 100%;
                max-width: 100%;
                height: auto;
                aspect-ratio: 1;
                max-width: min(90vw, 400px);
                margin: 10px auto;
            }
            
            .wheel-wrapper {
                width: 100%;
                height: 100%;
                position: relative;
                aspect-ratio: 1;
            }
            
            #wheelCanvas {
                width: 100% !important;
                height: 100% !important;
                max-width: 100%;
                max-height: 100%;
            }
            
            .wheel-pointer {
                top: -25px;
                border-left-width: 18px;
                border-right-width: 18px;
                border-top-width: 30px;
            }
            
            .wheel-center {
                width: 110px;
                height: 110px;
                padding: 12px;
            }
            
            .spin-controls {
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .spin-options {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }
            
            .spin-option-btn {
                width: 100%;
                max-width: 100%;
                padding: 14px 20px;
                font-size: 15px;
            }
            
            .spin-btn {
                width: 100%;
                max-width: 100%;
                padding: 16px 30px;
                font-size: 18px;
            }
            
            .info-panels {
                width: 100%;
            }
            
            .info-panel {
                min-width: 100%;
                width: 100%;
            }
            
            .modal-content {
                width: 95%;
                margin: 20px auto;
                padding: 20px;
            }
            
            .modal-header h2 {
                font-size: 22px;
            }
        }
        
        @media (max-width: 480px) {
            .lucky-wheel-header h1 {
                font-size: 24px;
                letter-spacing: 1px;
            }
            
            .wheel-container {
                max-width: min(85vw, 350px);
            }
            
            .wheel-center {
                width: 90px;
                height: 90px;
                padding: 10px;
            }
            
            .wheel-pointer {
                top: -20px;
                border-left-width: 15px;
                border-right-width: 15px;
                border-top-width: 25px;
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
            
            <!-- Spin Controls -->
            <div class="spin-controls">
                <div class="spin-options">
                    <button class="spin-option-btn active" data-count="1" onclick="selectSpinOption(1)">
                        <i class="fas fa-dice-one"></i> Quay 1 lần
                    </button>
                    <button class="spin-option-btn" data-count="20" onclick="selectSpinOption(20)">
                        <i class="fas fa-dice"></i> Quay 20 lần
                    </button>
                </div>
                <button class="spin-btn" onclick="spin()" id="spinBtn">
                    <i class="fas fa-dharmachakra"></i> QUAY NGAY
                </button>
            </div>
            
            <!-- Spin Status -->
            <div class="spin-status" id="spinStatus"></div>
            
            <!-- Wheel Section -->
            <div class="wheel-section">
                <div class="wheel-main">
                    <div class="wheel-container">
                        <div class="wheel-pointer"></div>
                        <div class="wheel-wrapper">
                            <canvas id="wheelCanvas" width="500" height="500"></canvas>
                            <div class="wheel-center">
                                <img src="assets/images/icon-game.png" alt="Game Icon">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Info Panels -->
                <div class="info-panels">
                    <!-- Accumulated Rewards -->
                    <div class="info-panel rewards-panel">
                        <h3><i class="fas fa-gift"></i> Phần Thưởng Tích Lũy</h3>
                        <div id="totalSpinsDisplay" class="total-spins-display">
                            <div class="total-spins-label">Tổng số vòng quay</div>
                            <div class="total-spins-value" id="totalSpinsValue">0</div>
                        </div>
                        <div id="accumulatedRewards">
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin"></i> Đang tải...
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Rewards -->
                    <div class="info-panel rewards-panel">
                        <h3><i class="fas fa-trophy"></i> Vật Phẩm Đã Trúng</h3>
                        <div id="pendingRewards">
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
        let selectedSpinCount = 1; // Default: 1 lần
        
        // Load initial data
        $(document).ready(function() {
            // Initial canvas resize
            setTimeout(function() {
                resizeCanvas();
            }, 100);
            
            loadConfig();
            loadItems();
            loadPendingRewards();
            loadAccumulatedRewards();
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
                        // Resize and render wheel
                        resizeCanvas();
                    }
                }
            });
        }
        
        // Handle window resize for responsive canvas
        let resizeTimeout;
        $(window).on('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                resizeCanvas();
            }, 250);
        });
        
        // Initial resize on page load
        $(window).on('load', function() {
            setTimeout(resizeCanvas, 100);
        });
        
        // Wheel colors - Deep rich reds and vibrant colors
        const WHEEL_COLORS = [
            "hsl(0, 85%, 45%)",     // Deep Red
            "hsl(0, 80%, 50%)",     // Medium Red
            "hsl(0, 75%, 48%)",     // Dark Red
            "hsl(0, 85%, 52%)",     // Bright Red
            "hsl(210, 90%, 50%)",   // Deep Blue
            "hsl(140, 70%, 40%)",   // Deep Green
            "hsl(45, 100%, 45%)",   // Rich Gold
            "hsl(280, 80%, 50%)",   // Deep Purple
            "hsl(25, 95%, 50%)",    // Deep Orange
            "hsl(330, 85%, 55%)",   // Deep Pink
        ];
        
        let currentRotation = 0;
        
        // Helper function to adjust brightness of HSL color
        function adjustBrightness(hslColor, amount) {
            const match = hslColor.match(/hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)/);
            if (!match) return hslColor;
            const h = parseInt(match[1]);
            const s = parseInt(match[2]);
            let l = parseInt(match[3]) + amount;
            l = Math.max(0, Math.min(100, l));
            return `hsl(${h}, ${s}%, ${l}%)`;
        }
        
        // Resize canvas for responsive design
        function resizeCanvas() {
            const canvas = document.getElementById('wheelCanvas');
            if (!canvas) return;
            
            const wrapper = canvas.parentElement; // wheel-wrapper
            if (!wrapper) return;
            
            // Get wrapper size (it's responsive via CSS)
            const wrapperWidth = wrapper.clientWidth || wrapper.offsetWidth;
            const wrapperHeight = wrapper.clientHeight || wrapper.offsetHeight;
            
            // Use the smaller dimension to maintain square aspect ratio
            // Max 500px for desktop, responsive for mobile
            const maxSize = window.innerWidth <= 768 ? Math.min(window.innerWidth * 0.9, 400) : 500;
            const size = Math.min(wrapperWidth, wrapperHeight, maxSize);
            
            if (size > 0) {
                // Set canvas size (internal resolution - for drawing)
                canvas.width = size;
                canvas.height = size;
                
                // Set CSS size (display size - for layout)
                canvas.style.width = size + 'px';
                canvas.style.height = size + 'px';
                
                // Re-render wheel with new size if items are loaded
                if (wheelItems.length > 0) {
                    renderWheel();
                }
            }
        }
        
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
                    color = "hsl(280, 85%, 60%)"; // Bright Purple for rare items
                }
                
                // Create gradient for depth
                const gradient = ctx.createRadialGradient(
                    centerX, centerY, radius * 0.3,
                    centerX, centerY, radius
                );
                gradient.addColorStop(0, color);
                gradient.addColorStop(1, adjustBrightness(color, -15));
                
                // Draw segment
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();
                
                // Enhanced border
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
                ctx.lineWidth = 2.5;
                ctx.stroke();
                
                // Inner highlight
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius * 0.95, startAngle, endAngle);
                ctx.stroke();
                
                // Draw text with enhanced styling
                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(startAngle + segmentAngle / 2);
                ctx.textAlign = 'right';
                
                // Text shadow for better readability
                ctx.shadowColor = 'rgba(0, 0, 0, 0.9)';
                ctx.shadowBlur = 4;
                ctx.shadowOffsetX = 1;
                ctx.shadowOffsetY = 1;
                
                // Main text - adjust font size based on canvas size
                const fontSize = Math.max(10, Math.min(14, canvas.width / 35));
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold ' + fontSize + 'px Arial';
                
                // Adjust text length based on canvas size
                const maxTextLength = canvas.width < 400 ? 12 : 15;
                const text = item.item_name.length > maxTextLength 
                    ? item.item_name.substring(0, maxTextLength - 2) + '...' 
                    : item.item_name;
                ctx.fillText(text, radius - (canvas.width < 400 ? 15 : 20), 5);
                
                // Rare item indicator
                if (item.is_rare) {
                    ctx.fillStyle = '#ffd700';
                    ctx.font = 'bold ' + Math.max(10, fontSize - 2) + 'px Arial';
                    ctx.fillText('⭐', radius - (canvas.width < 400 ? 3 : 5), -8);
                }
                
                ctx.restore();
            });
        }
        
        // Select spin option
        function selectSpinOption(count) {
            if (isSpinning) return;
            
            selectedSpinCount = count;
            
            // Update active state
            $('.spin-option-btn').removeClass('active');
            $(`.spin-option-btn[data-count="${count}"]`).addClass('active');
        }
        
        // Spin function
        function spin() {
            if (isSpinning) {
                return;
            }
            
            const count = selectedSpinCount;
            
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
        
        // Spin single - Rewritten with improved rotation calculation
        function spinSingle() {
            $('#spinStatus').text('Đang quay...');
            
            $.ajax({
                url: '/api/lucky_wheel/spin.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ spin_count: 1 }),
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    try {
                        if (response && response.success && response.data && response.data.results && response.data.results.length > 0) {
                            const result = response.data.results[0];
                            const itemIndex = wheelItems.findIndex(i => i.id === result.item_id);
                            
                            if (itemIndex === -1) {
                                alert('Không tìm thấy vật phẩm trong danh sách!');
                                isSpinning = false;
                                disableSpinButtons(false);
                                $('#spinStatus').text('');
                                return;
                            }
                            
                            // Calculate rotation to stop at the correct item
                            // Wheel rendering: segments start from -90 degrees (top position)
                            // Pointer: fixed at top (0 degrees)
                            // Goal: rotate wheel so winning segment's center aligns with pointer (top)
                            
                            const canvas = document.getElementById('wheelCanvas');
                            const totalItems = wheelItems.length;
                            const segmentAngle = 360 / totalItems;
                            
                            // Calculate the angle of the winning segment's center
                            // Each segment starts at: (index * segmentAngle - 90) degrees
                            // Segment center is at: startAngle + segmentAngle/2
                            const segmentStartAngle = (itemIndex * segmentAngle) - 90;
                            const segmentCenterAngle = segmentStartAngle + (segmentAngle / 2);
                            
                            // To align segment center with pointer (top = 0 degrees):
                            // We need to rotate by: 360 - segmentCenterAngle
                            // But since wheel is already at currentRotation, we need:
                            // finalRotation = currentRotation + additionalRotation
                            // where additionalRotation = 360 - segmentCenterAngle
                            
                            // Add multiple full rotations for visual effect (5-8 rotations)
                            const fullRotations = 5 + Math.random() * 3;
                            const additionalRotation = (360 * fullRotations) + (360 - segmentCenterAngle);
                            const finalRotation = currentRotation + additionalRotation;
                            
                            // Apply rotation with CSS transition
                            canvas.style.transition = 'transform 5s cubic-bezier(0.17, 0.67, 0.12, 0.99)';
                            canvas.style.transform = `rotate(${finalRotation}deg)`;
                            
                            // Update current rotation (normalize to 0-360 range for next spin)
                            currentRotation = finalRotation % 360;
                            
                            // Wait for animation to complete (5 seconds)
                            setTimeout(function() {
                                try {
                                    updateSilkBalance(response.data.total_cost);
                                    showResultsModal([result], 1);
                                    showConfetti();
                                    loadPendingRewards();
                                    loadAccumulatedRewards();
                                } catch (e) {
                                    console.error('Error processing spin result:', e);
                                } finally {
                                    isSpinning = false;
                                    disableSpinButtons(false);
                                    $('#spinStatus').text('');
                                }
                            }, 5000);
                        } else {
                            const errorMsg = response?.error || 'Có lỗi xảy ra khi quay';
                            alert('Lỗi: ' + errorMsg);
                            isSpinning = false;
                            disableSpinButtons(false);
                            $('#spinStatus').text('');
                        }
                    } catch (e) {
                        console.error('Error processing spin response:', e);
                        alert('Lỗi xử lý kết quả quay');
                        isSpinning = false;
                        disableSpinButtons(false);
                        $('#spinStatus').text('');
                    }
                },
                error: function(xhr, status, error) {
                    isSpinning = false;
                    disableSpinButtons(false);
                    $('#spinStatus').text('');
                    
                    let errorMsg = 'Có lỗi xảy ra';
                    if (status === 'timeout') {
                        errorMsg = 'Quá thời gian chờ. Vui lòng thử lại.';
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (error) {
                        errorMsg = error;
                    }
                    alert('Lỗi: ' + errorMsg);
                }
            });
        }
        
        // Spin multiple - Rewritten with improved error handling
        function spinMultiple(count) {
            $('#spinStatus').text('Đang quay ' + count + ' lần...');
            
            // Symbolic animation - chỉ quay 1 vòng tượng trưng
            const canvas = document.getElementById('wheelCanvas');
            const startRotation = currentRotation;
            const targetRotation = startRotation + 360; // Quay đúng 1 vòng
            
            // Apply CSS transition for smooth rotation
            canvas.style.transition = 'transform 2s cubic-bezier(0.17, 0.67, 0.12, 0.99)';
            canvas.style.transform = `rotate(${targetRotation}deg)`;
            currentRotation = targetRotation % 360;
            
            // Call API while animation is running
            $.ajax({
                url: '/api/lucky_wheel/spin.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ spin_count: count }),
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    // Wait for animation to complete (2 seconds)
                    setTimeout(function() {
                        try {
                            if (response && response.success && response.data && response.data.results) {
                                updateSilkBalance(response.data.total_cost);
                                showResultsModal(response.data.results, count);
                                showConfetti();
                                loadPendingRewards();
                                loadAccumulatedRewards();
                            } else {
                                const errorMsg = response?.error || 'Có lỗi xảy ra khi quay';
                                alert('Lỗi: ' + errorMsg);
                            }
                        } catch (e) {
                            console.error('Error processing spin result:', e);
                            alert('Lỗi xử lý kết quả quay');
                        } finally {
                            isSpinning = false;
                            disableSpinButtons(false);
                            $('#spinStatus').text('');
                        }
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    // Reset immediately on error
                    isSpinning = false;
                    disableSpinButtons(false);
                    $('#spinStatus').text('');
                    
                    let errorMsg = 'Có lỗi xảy ra';
                    if (status === 'timeout') {
                        errorMsg = 'Quá thời gian chờ. Vui lòng thử lại.';
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (error) {
                        errorMsg = error;
                    }
                    alert('Lỗi: ' + errorMsg);
                }
            });
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
        
        // Load accumulated rewards
        function loadAccumulatedRewards() {
            console.log('Loading accumulated rewards...');
            
            const container = $('#accumulatedRewards');
            if (container.length === 0) {
                console.error('accumulatedRewards container not found');
                return;
            }
            
            // Show loading state
            container.html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');
            
            $.ajax({
                url: '/api/lucky_wheel/get_available_accumulated_rewards.php',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    console.log('Accumulated rewards response:', response);
                    
                    container.empty();
                    
                    if (response.success && response.data) {
                        // Update total spins display
                        const totalSpins = response.data.total_spins || 0;
                        const totalSpinsValue = $('#totalSpinsValue');
                        if (totalSpinsValue.length > 0) {
                            totalSpinsValue.text(totalSpins.toLocaleString('vi-VN'));
                        }
                        
                        const rewards = response.data.rewards || [];
                        console.log('Found', rewards.length, 'accumulated rewards');
                        
                        if (rewards.length > 0) {
                            rewards.forEach(function(reward) {
                                const progressPercent = Math.round(reward.progress || 0);
                                // Handle boolean conversion (may come as string "true"/"false" or boolean)
                                const canClaim = reward.can_claim === true || reward.can_claim === 'true' || reward.can_claim === 1;
                                const hasReached = reward.total_spins >= reward.required_spins;
                                
                                // Determine button text and state
                                let buttonHtml = '';
                                if (canClaim) {
                                    // Can claim: reached requirement and not yet claimed
                                    buttonHtml = '<button class="claim-accumulated-btn" onclick="claimAccumulatedReward(' + reward.id + ', \'' + escapeHtml(reward.item_name) + '\')">Nhận</button>';
                                } else if (hasReached) {
                                    // Already claimed: reached but can't claim (already claimed)
                                    buttonHtml = '<button class="claim-accumulated-btn" disabled style="opacity: 0.5; cursor: not-allowed; background: #696969;">Đã nhận</button>';
                                } else {
                                    // Not reached yet
                                    buttonHtml = '<button class="claim-accumulated-btn" disabled style="opacity: 0.5; cursor: not-allowed;">Chưa đạt</button>';
                                }
                                
                                const item = $('<div>')
                                    .addClass('accumulated-reward-item')
                                    .html(
                                        '<div class="accumulated-reward-info">' +
                                        '<div class="accumulated-reward-name">' + escapeHtml(reward.item_name) + ' x' + reward.quantity + '</div>' +
                                        '<div class="accumulated-reward-details">Mức đạt: ' + reward.required_spins + ' vòng</div>' +
                                        '<div class="accumulated-reward-progress">Tiến độ: ' + reward.total_spins + ' / ' + reward.required_spins + ' vòng (' + progressPercent + '%)</div>' +
                                        '</div>' +
                                        buttonHtml
                                    );
                                container.append(item);
                            });
                        } else {
                            container.html('<div class="empty-state">Chưa có phần thưởng tích lũy nào</div>');
                        }
                    } else {
                        console.error('Response not successful:', response);
                        container.html('<div class="empty-state">' + (response.error || 'Lỗi tải dữ liệu') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading accumulated rewards:', status, error, xhr);
                    const errorMsg = xhr.responseJSON?.error || 'Lỗi kết nối: ' + error;
                    container.html('<div class="empty-state">' + errorMsg + '</div>');
                }
            });
        }
        
        // Claim accumulated reward
        function claimAccumulatedReward(accumulatedItemId, itemName) {
            if (!confirm('Xác nhận nhận phần thưởng "' + itemName + '"?')) {
                return;
            }
            
            // Disable button
            const btn = event.target;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';
            
            $.ajax({
                url: '/api/lucky_wheel/claim_accumulated_reward.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    accumulated_item_id: accumulatedItemId
                }),
                success: function(response) {
                    if (response.success) {
                        alert('Đã nhận phần thưởng thành công!');
                        // Reload accumulated rewards to update list
                        loadAccumulatedRewards();
                    } else {
                        alert('Lỗi: ' + (response.error || 'Không thể nhận phần thưởng'));
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    const errorMsg = response?.error || 'Lỗi kết nối';
                    alert('Lỗi: ' + errorMsg);
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            });
        }
        
        // Escape HTML helper
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        // Claim reward
        function claimReward(rewardId) {
            if (!confirm('Xác nhận nhận phần thưởng này?')) {
                return;
            }
            
            // Disable button and show loading
            const btn = $('button[onclick="claimReward(' + rewardId + ')"]');
            btn.prop('disabled', true).addClass('claiming').text('Đang xử lý...');
            
            $.ajax({
                url: '/api/lucky_wheel/claim_reward.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ reward_id: rewardId }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('#spinStatus').text('🎉 Đã nhận phần thưởng thành công!');
                        setTimeout(function() {
                            $('#spinStatus').text('');
                        }, 3000);
                        
                        // Reload rewards list
                        loadPendingRewards();
                    } else {
                        alert('Lỗi: ' + response.error);
                        btn.prop('disabled', false).removeClass('claiming').text('Nhận');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert('Lỗi: ' + (response?.error || 'Có lỗi xảy ra'));
                    btn.prop('disabled', false).removeClass('claiming').text('Nhận');
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
            $('#spinBtn').prop('disabled', disable);
            $('.spin-option-btn').prop('disabled', disable);
            
            if (disable) {
                $('#spinBtn').html('<i class="fas fa-spinner fa-spin"></i> Đang quay...');
            } else {
                $('#spinBtn').html('<i class="fas fa-dharmachakra"></i> QUAY NGAY');
            }
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
