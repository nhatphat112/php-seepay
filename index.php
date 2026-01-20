<?php
require_once __DIR__ . '/includes/home_content.php';
require_once __DIR__ . '/includes/lucky_wheel_helper.php';

// Load content from database
$sliders = HomeContent::getSliders();
$socialLinks = HomeContent::getSocialLinks();
$serverInfo = HomeContent::getServerInfo();
$weeklyEvents = HomeContent::getWeeklyEvents();
$qrcode = HomeContent::getQRCode();
$newsAll = HomeContent::getNews();
$newsHot = HomeContent::getNews('Tin Nóng');
$newsEvent = HomeContent::getNews('Sự Kiện');
$newsUpdate = HomeContent::getNews('Cập Nhật');

// Load lucky wheel rare wins (server-side)
$rareWins = getRecentRareWins(20);

// Server info is just plain text, no parsing needed
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Trang chủ Song Long Tranh Bá - Game nhập vai huyền thoại 2006</title>
    <meta name="description" content="Trang web chính thức của Song Long Tranh Bá. Game nhập vai huyền thoại 2006."/>
    <meta name="keywords" content="Song Long Tranh Bá, Game nhập vai huyền thoại 2006, Game online, Free game"/>
    
    <link rel="icon" href="images/favicon.ico"/>
    
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/vendor.css" />
    <link rel="stylesheet" href="assets/css/main1bce.css?v=6" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/font-override.css" />
    
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .job-menu-item {
            margin-left: 340px !important;
        }
        
        @media (max-width: 1199px) {
            .job-menu-item {
                margin-left: 0 !important;
            }
        }
        
        .auth-links {
            display: flex;
            gap: 10px;
            margin-right: 15px;
        }
        
        .btn-auth {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .btn-login {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .btn-login:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            font-weight: 700;
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
        
        .btn-auth i {
            font-size: 12px;
        }
        .slide-new-info-new-home{
            width: 100% !important;
        }
        .img-icongame{
            border-radius: 50% !important;
        }
        .home-page{
            background: black !important;
        }
        
        @media (max-width: 1199px) {
            .auth-links {
                display: none !important;
            }
        }
        
        /* Combined Leaderboard and Slider Container */
        .leaderboard-slider-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            position: relative;
            margin-bottom: 30px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            margin-top:80px;
        }
        
        .leaderboard-section-left {
            flex: 0 0 40%;
            min-width: 0;
            max-width: 40%;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .slide-section-right {
            flex: 1 1 60%;
            min-width: 0;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .slide-section-right .slide-new-info-new-home {
            width: 100%;
            height: 100%;
        }
        
        .slide-section-right .slide-hotevent {
            width: 100%;
            height: 100%;
        }
        
        .slide-section-right .slide-new-home {
            width: 100%;
            height: 100%;
        }
        
        .home-leaderboard-box {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #e8c088;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(232, 192, 136, 0.3);
            min-height: 200px;
        }
        
        .home-leaderboard-title {
            color: #e8c088;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 15px 0;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .home-leaderboard-title i {
            color: #ffd700;
        }
        
        .home-leaderboard-list {
            display: grid;
            gap: 10px;
        }
        
        .home-leaderboard-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: rgba(30, 35, 60, 0.6);
            border-radius: 8px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .home-leaderboard-item:hover {
            background: rgba(30, 35, 60, 0.8);
            transform: translateX(5px);
        }
        
        .home-leaderboard-item.rank-1 {
            border-left-color: #ffd700;
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.2), rgba(30, 35, 60, 0.6));
        }
        
        .home-leaderboard-item.rank-2 {
            border-left-color: #c0c0c0;
            background: linear-gradient(90deg, rgba(192, 192, 192, 0.2), rgba(30, 35, 60, 0.6));
        }
        
        .home-leaderboard-item.rank-3 {
            border-left-color: #cd7f32;
            background: linear-gradient(90deg, rgba(205, 127, 50, 0.2), rgba(30, 35, 60, 0.6));
        }
        
        .home-leaderboard-rank {
            font-size: 14px;
            font-weight: bold;
            color: #e8c088;
            min-width: 40px;
            text-align: center;
        }
        
        .home-leaderboard-username {
            flex: 1;
            color: #87ceeb;
            font-weight: 500;
            margin-left: 10px;
            font-size: 13px;
        }
        
        .home-leaderboard-spins {
            color: #ffd700;
            font-weight: bold;
            font-size: 14px;
            min-width: 70px;
            text-align: right;
        }
        
        .home-leaderboard-season {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(232, 192, 136, 0.3);
            color: #87ceeb;
            font-size: 0.85rem;
            text-align: center;
        }
        
        @media (max-width: 992px) {
            .leaderboard-slider-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .leaderboard-section-left,
            .slide-section-right {
                flex: 1 1 100%;
                width: 100%;
                min-width: 100%;
                max-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .home-leaderboard-box {
                padding: 15px;
            }
            
            .home-leaderboard-title {
                font-size: 16px;
            }
            
            .home-leaderboard-item {
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .home-leaderboard-username {
                font-size: 12px;
            }
            
            .home-leaderboard-spins {
                font-size: 12px;
            }
        }
        
        /* Lucky Wheel Ticker Styles */
        .lucky-wheel-ticker {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(22, 33, 62, 0.95) 100%);
            border: 6px solid #e8c088;
            overflow: hidden;
            position: relative;
            box-shadow: 0 2px 10px rgba(232, 192, 136, 0.2);
        }
        
        .ticker-container {
            display: flex;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .ticker-label {
            color: #e8c088;
            font-weight: bold;
            font-size: 16px;
            margin-right: 20px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            padding: 8px 16px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .ticker-label i {
            color: #ffd700;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .ticker-content {
            flex: 1;
            overflow: hidden;
            position: relative;
            height: 30px;
        }
        
        .ticker-scroll {
            display: flex;
            align-items: center;
            gap: 40px;
            white-space: nowrap;
            animation: scroll 30s linear infinite;
        }
        
        .ticker-scroll:hover {
            animation-play-state: paused;
        }
        
        @keyframes scroll {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        
        .ticker-item {
            display: inline-block;
            color: #87ceeb;
            font-size: 15px;
            padding: 5px 15px;
            background: rgba(232, 192, 136, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(232, 192, 136, 0.3);
            white-space: nowrap;
        }
        
        .ticker-item .username {
            color: #ffd700;
            font-weight: bold;
        }
        
        .ticker-item .item-name {
            color: #ff6b6b;
            font-weight: bold;
        }
        
        .ticker-item .separator {
            color: #87ceeb;
            margin: 0 8px;
        }
        
        .ticker-item .time-ago {
            color: rgba(135, 206, 235, 0.7);
            font-size: 13px;
            margin-left: 8px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .ticker-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .ticker-label {
                margin-right: 0;
                font-size: 14px;
                padding: 6px 12px;
            }
            
            .ticker-item {
                font-size: 13px;
                padding: 4px 12px;
            }
            
            .ticker-scroll {
                animation: scroll 240s linear infinite;
            }
        }
    </style>
    
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
</head>

<body class="home-page">

    <div class="header">
        <input id="toggle-hambuger" type="checkbox" style="display:none">
        <nav class="navbar clearfix">
            <div class="container">
                <a href="index.php">
                    <img src="assets/images/logo.png" style="border-radius: 50%" alt="Silkroad Logo" class="logo-top hidden-1199">
                </a>

                <a href="index.php" class="hidden-1200">
                    <img class="icon-game" src="assets/images/icon-game.png">
                </a>

                <div class="info-game hidden-1200">
                    <p class="name-game t-upper">CON ĐƯỜNG TƠ LỤA<br>MOBILE</p>
                </div>
                
                <div class="right-nav">
                    <div class="auth-links hidden-1200">
                        <a href="login.php" class="btn-auth btn-login">
                            <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                        </a>
                        <a href="register.php" class="btn-auth btn-register">
                            <i class="fas fa-user-plus"></i> Đăng Ký
                        </a>
                    </div>
                    <div class="link-head-setting hidden-1200">
                        <div class="link-hs icon-fp d-flex a-center j-center link-show-com-pc link-show-com-mb t-center p-relative">
                            <img src="assets/images/icons/com.png" alt="">
                            <div class="list-link-show-com p-absolute t-upper">
                                <?php if (isset($socialLinks['facebook'])): ?>
                                <a target="_blank" href="<?php echo htmlspecialchars($socialLinks['facebook']['url']); ?>">Facebook</a>
                                <?php endif; ?>
                                <?php if (isset($socialLinks['facebook_group'])): ?>
                                <a target="_blank" href="<?php echo htmlspecialchars($socialLinks['facebook_group']['url']); ?>">Group Facebook</a>
                                <?php endif; ?>
                                <?php if (isset($socialLinks['discord'])): ?>
                                <a target="_blank" href="<?php echo htmlspecialchars($socialLinks['discord']['url']); ?>">Discord</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="icon-hamburger hidden-1200">
                        <label for="toggle-hambuger" id="hambuger">
                            <div class="inner-hambuger"></div>
                        </label>
                    </div>
                </div>

                <div class="navbar-content t-upper vi">
                    <ul id="menu">
                        <li class=""><a href="index.php" class="a100 home-page">Trang Chủ</a></li>
                        <li class=""><a href="#tin-tuc" class="a100">Tin Tức</a></li>
                        <li class=""><a href="#social-links" class="a100">Facebook - Nhóm FB - Zalo</a></li>
                        <li class=""><a href="#hoat-dong" class="a100">Hoạt Động</a></li>
                        <li class=""><a href="payment.php" class="a100">MUA SILK</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <div class="new-home container">
        <!-- <div class="title-frame t-center t-upper d-flex a-center j-center">
            <img src="assets/images/title/img-title3860.png" alt="">
            <div class="name-title vi">Hệ Thống Việc Làm</div>
            <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
        </div> -->

        <!-- Combined Section: BẢNG XẾP HẠNG (Trái) và SLIDER (Phải) -->
        <div class="leaderboard-slider-container m-auto">
            <!-- Bảng Xếp Hạng - Bên Trái -->
            <div class="leaderboard-section-left">
                <div class="home-leaderboard-box">
                    <h3 class="home-leaderboard-title">
                        <i class="fas fa-trophy"></i> Bảng Xếp Hạng Vòng Quay
                    </h3>
                    <div id="homeLeaderboardContent">
                        <div style="text-align: center; padding: 20px; color: #87ceeb;">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải...
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slider - Bên Phải -->
            <div class="slide-section-right">
                <div class="">
                    <div class="slide-hotevent p-relative">
                        <div class="slide-new-home slick-custom-dots">
                            <?php foreach ($sliders as $slider): ?>
                            <a href="<?php echo htmlspecialchars($slider['LinkURL'] ?: '#'); ?>" class="item-slide-nh">
                                <img src="<?php echo htmlspecialchars($slider['ImagePath']); ?>" alt="Slider">
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lucky-wheel-ticker container" id="luckyWheelTicker">
        <div class="ticker-container">
            <div class="ticker-label">
                <i class="fas fa-trophy"></i> Vòng quay may mắn
            </div>
            <div class="ticker-content" id="tickerContent">
                <?php if (!empty($rareWins)): ?>
                    <div class="ticker-scroll">
                        <?php 
                        // Duplicate items for seamless loop
                        $duplicatedWins = array_merge($rareWins, $rareWins);
                        foreach ($duplicatedWins as $win): 
                            // Format time ago using full current datetime for accurate seconds/minutes
                            $wonDate = new DateTime($win['WonDate']);
                            $now = new DateTime();
                            $diff = $now->diff($wonDate);
                            
                            $timeAgo = '';
                            if ($diff->days > 0) {
                                if ($diff->days < 7) {
                                    $timeAgo = $diff->days . ' ngày trước';
                                } else {
                                    $timeAgo = $wonDate->format('d/m') . ($wonDate->format('Y') !== $now->format('Y') ? '/' . $wonDate->format('Y') : '');
                                }
                            } elseif ($diff->h > 0) {
                                $timeAgo = $diff->h . ' giờ trước';
                            } elseif ($diff->i > 0) {
                                $timeAgo = $diff->i . ' phút trước';
                            } else {
                                $timeAgo = 'vừa xong';
                            }
                        ?>
                            <div class="ticker-item">
                                <span class="username"><?php echo htmlspecialchars($win['Username']); ?></span>
                                <span class="separator">đã trúng</span>
                                <span class="item-name"><?php echo htmlspecialchars($win['ItemName']); ?></span>
                                <span class="time-ago">(<?php echo $timeAgo; ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ticker-item">Chưa có người chơi nào trúng vật phẩm hiếm</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <!-- Lucky Wheel Ticker -->

    <div class="new-home" id="news">
        <div class="title-frame t-center t-upper d-flex a-center j-center">
            <img src="assets/images/title/img-title3860.png" alt="">
            <div class="name-title vi">Sự Kiện Trong Tuần</div>
            <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
        </div>

        <div class="slide-new-info-new-home">
            <section class="event-schedule-section-new" id="hoat-dong">
                <div class="container">
                    <div class="event-schedule-grid-new">
                <div class="event-title-box-new">
                    <div class="box-border-new event-labels-box">
                        <div class="event-labels-content">
                            <div class="label-time">THỜI GIAN</div>
                            <div class="label-day">NGÀY</div>
                            <div class="label-divider">
                                <div class="divider-line"></div>
                                <div class="divider-emblem"></div>
                            </div>
                            <div class="label-event-name">TÊN SỰ KIỆN</div>
                        </div>
                    </div>
                </div>

                <div class="event-banner-middle-new">
                    <div class="slide-hotevent p-relative">
                        <div class="list-hotevent d-flex a-center j-center">
                                <div class="list-item-hotevent p-relative f-cambria d-flex a-center j-center t-center">
                                <img src="assets/images/box-new/line-hotevents.png" class="line-hev p-absolute">
                                
                                <?php foreach ($weeklyEvents as $event): ?>
                                <div class="item-hotevent">
                                    <div class="time-day-hev">
                                        <div class="time-hev"><?php echo htmlspecialchars($event['EventTime']); ?></div>
                                        <div class="day-hev"><?php echo htmlspecialchars($event['EventDay']); ?></div>
                                    </div>
                                    <div class="dot-hev">
                                        <img src="assets/images/box-new/dot-hot-event.png" alt="">
                                    </div>
                                    <div class="title-hev"><?php echo htmlspecialchars($event['EventTitle']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="qr-code-box-new">
                    <div class="box-border-new qr-container-new">
                        <div class="qr-text-new">
                            <?php 
                            $qrText = $qrcode ? $qrcode['Description'] : 'Quét QR để vào cộng đồng';
                            // Split text into lines if too long
                            $words = explode(' ', $qrText);
                            $lines = [];
                            $currentLine = '';
                            foreach ($words as $word) {
                                if (strlen($currentLine . ' ' . $word) <= 20) {
                                    $currentLine = $currentLine ? $currentLine . ' ' . $word : $word;
                                } else {
                                    if ($currentLine) $lines[] = $currentLine;
                                    $currentLine = $word;
                                }
                            }
                            if ($currentLine) $lines[] = $currentLine;
                            $displayText = implode('<br>', array_slice($lines, 0, 3)); // Max 3 lines
                            ?>
                            <div class="qr-title-text"><?php echo $displayText; ?></div>
                        </div>
                        <div class="qr-image-container-new">
                            <?php if ($qrcode && $qrcode['ImagePath']): ?>
                            <img src="<?php echo htmlspecialchars($qrcode['ImagePath']); ?>" alt="QR Code" class="qr-image-new">
                            <?php else: ?>
                            <div class="qr-placeholder-new">
                                <span style="color: rgba(0, 0, 0, 0.3); font-size: 12px;">QR Code</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </section>
        </div>

    </div>

    <style>
        .event-schedule-section-new {
            width: 100%;
            padding: 60px 0;
            background: transparent;
        }

        .event-schedule-section-new .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .server-and-news {
            width: 100%;
        }

        .server-news-section-new .svinfo-right {
            background: transparent;
        }

        .server-news-section-new .title-svinfo-right {
            color: #e8c088;
        }

        .server-news-section-new .title-svinfo-right .t-upper {
            color: #e8c088;
        }

        .server-news-section-new .list-svinfo {
            background: transparent;
        }

        .server-news-section-new .item-svinfo {
            color: #e8c088;
        }

        .server-news-section-new .if-left-svinfo {
            color: rgba(232, 192, 136, 0.8);
        }

        .server-news-section-new .if-right-svinfo {
            color: #e8c088;
        }

        .server-news-section-new .join-socialright {
            background: transparent;
        }

        .server-news-section-new .title-jsocialright {
            color: #e8c088;
        }

        .server-news-section-new .link-socialright {
            transition: all 0.3s;
        }

        .server-news-section-new .link-socialright:hover {
            transform: scale(1.1);
            opacity: 0.8;
        }

        .server-news-section-new .tab-new-nh {
            background: transparent;
        }

        .server-news-section-new .item-tab-new-nh {
            color: #e8c088;
            background: transparent;
            border-bottom: 2px solid transparent;
        }

        .server-news-section-new .item-tab-new-nh:hover {
            background: rgba(232, 192, 136, 0.1);
            border-bottom-color: rgba(232, 192, 136, 0.3);
        }

        .server-news-section-new .item-tab-new-nh.active {
            background: rgba(232, 192, 136, 0.2);
            border-bottom-color: #e8c088;
            color: #e8c088;
        }

        .server-news-section-new .list-new-detail-nh {
            background: transparent;
        }

        .server-news-section-new .item-news {
            color: #e8c088;
            border-bottom: 1px solid rgba(232, 192, 136, 0.2);
        }

        .server-news-section-new .item-news:hover {
            background: rgba(232, 192, 136, 0.05);
        }

        .server-news-section-new .cat {
            color: #e8c088;
        }

        .server-news-section-new .title-news {
            color: #e8c088;
        }

        .server-news-section-new .date-news {
            color: rgba(232, 192, 136, 0.7);
        }

        .event-schedule-grid-new {
            display: grid;
            grid-template-columns: 200px 1fr 200px;
            gap: 30px;
            align-items: stretch;
        }

        .event-title-box-new {
            height: 100%;
        }

        .box-border-new {
            border: 4px solid #000;
            border-radius: 8px;
            background: #e8c88;            box-shadow: 0 0 0 2px #FFD700;
            background: #transparent;
            padding: 15px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .event-labels-box {
            background: #1a1a1a;
            padding: 20px 15px;
        }

        .event-labels-content {
            text-align: center;
            width: 100%;
        }

        .label-time {
            color: #D4AF37;
            font-weight: 700;
            font-size: 1.1rem;
            line-height: 1.4;
            margin-bottom: 5px;
            font-family: sans-serif;
            text-transform: uppercase;
        }

        .label-day {
            color: #D4AF37;
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.4;
            margin-bottom: 15px;
            font-family: sans-serif;
            text-transform: uppercase;
        }

        .label-divider {
            position: relative;
            margin: 15px 0;
            height: 2px;
        }

        .divider-line {
            width: 100%;
            height: 2px;
            background: #D4AF37;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .divider-emblem {
            width: 16px;
            height: 16px;
            background: #D4AF37;
            transform: rotate(45deg);
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            border: 2px solid #1a1a1a;
        }

        .label-event-name {
            color: #e8c088;
            font-weight: 700;
            font-size: 1.1rem;
            line-height: 1.4;
            margin-top: 15px;
            font-family: sans-serif;
            text-transform: uppercase;
        }

        .event-banner-middle-new {
            height: 200px;
            position: relative;
        }

        .event-banner-middle-new .slide-hotevent {
            height: 100%;
            width: inherit;
        }

        .event-banner-middle-new .list-hotevent {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .event-banner-middle-new .list-item-hotevent {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            position: relative;
            gap: 30px;
            padding: 0 20px;
        }

        .event-banner-middle-new .item-hotevent {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            flex: 1;
            min-width: 0;
        }

        .qr-code-box-new {
            height: 100%;
        }

        .qr-container-new {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 12px;
            gap: 8px;
            height: 100%;
            box-sizing: border-box;
        }

        .qr-text-new {
            width: 100%;
            text-align: center;
            flex-shrink: 0;
        }

        .qr-title-text {
            color: #e8c088;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            font-family: sans-serif;
            margin-bottom: 0;
            min-height: 35px;
            max-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .qr-image-container-new {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-height: 0;
        }

        .qr-image-new {
            max-width: 100%;
            max-height: 110px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
        }

        .qr-placeholder-new {
            width: 110px;
            height: 110px;
            background: rgba(0, 0, 0, 0.05);
            border: 2px dashed rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 1024px) {
            .event-schedule-grid-new {
                grid-template-columns: 180px 1fr 180px;
                gap: 20px;
            }

            .event-banner-middle-new {
                height: 180px; 
            }

            .qr-container-new {
                padding: 10px;
                gap: 6px;
            }

            .qr-title-text {
                font-size: 10px;
                min-height: 30px;
                max-height: 35px;
            }

            .qr-image-new {
                max-height: 100px;
            }

            .qr-placeholder-new {
                width: 100px;
                height: 100px;
            }
        }

        @media (max-width: 768px) {
            .event-schedule-grid-new {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .event-title-box-new,
            .qr-code-box-new,
            .event-banner-middle-new {
                height: auto;
            }

            .event-banner-middle-new {
                min-height: 250px;
            }

            .box-border-new {
                min-height: 150px;
            }

            .qr-placeholder-new {
                max-width: 200px;
                margin: 0 auto;
            }
        }

        .server-news-section-new {
            width: 100%;
            padding: 60px 0;
            background: transparent;
        }

        .server-news-section-new .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .server-news-section-new .new-tabhome-inforight {
            display: grid;
            grid-template-columns:50% 50%;
            gap: 30px;
            align-items: start;
        }

        .final-section-new {
            padding: 60px 0;
            background: transparent;
            width: 100%;
        }

        .final-section-new .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .final-section-grid-new {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .fanpage-box-new {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .fanpage-header-new {
            background: transparent; 
            color: #e8c088;
            padding: 15px 20px;
            text-align: center;
            border: none;
        }

        .fanpage-title-new {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e8c088;
        }

        .fanpage-content-new {
            background: transparent;
            border: none;
            padding: 30px;
            flex: 1;
        }

        .fanpage-links-new {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 10px 0;
        }

        .fanpage-link-item-new {
            padding: 12px 20px;
            background: rgba(232, 192, 136, 0.1);
            border: 1px solid rgba(232, 192, 136, 0.3);
            border-radius: 5px;
            text-decoration: none;
            color: #e8c088;
            transition: all 0.3s;
        }

        .fanpage-link-item-new:hover {
            background: rgba(232, 192, 136, 0.2);
            border-color: rgba(232, 192, 136, 0.5);
            transform: translateY(-2px);
        }

        .fanpage-link-item-new span {
            color: #e8c088;
            transition: color 0.3s;
        }

        .fanpage-link-item-new:hover span {
            color: #e8c088;
        }

        .fanpage-link-item-new img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

        .fanpage-link-item-new span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .job-system-mini-new {
            height: 100%;
        }

        .jobsystem-mini-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .bg-main-job-mini {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
        }

        .img-job-behind-mini {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.3;
        }

        .main-job-chienma-mini {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            height: 80%;
            display: flex;
            justify-content: space-around;
            align-items: center;
            gap: 10px;
        }

        .it-job-cma-mini {
            flex: 1;
            max-width: 120px;
            position: relative;
            transition: transform 0.3s;
        }

        .it-job-cma-mini:hover {
            transform: scale(1.05);
        }

        .img-cmald-mini {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .name-job-cma-mini {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.7rem; 
            color: #e8c088;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            white-space: nowrap;
        }
        .footer-session { 
            margin-top: 26px ;
        }
        .right-news{
            width: 100% !important;
        }

        @media (max-width: 968px) {
            .final-section-grid-new {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .bg-main-job-mini {
                height: 300px;
            }

            .it-job-cma-mini {
                max-width: 100px;
            }
        }

        @media (max-width: 968px) {
            .server-news-grid-new {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .server-info-content-new {
                min-height: 300px;
            }
        }
    </style>

    <div class="new-home" id="tin-tuc">
        <div class="title-frame t-center t-upper d-flex a-center j-center">
            <img src="assets/images/title/img-title3860.png" alt="">
            <div class="name-title vi">Máy Chủ Và Tin Tức</div>
            <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
        </div>

        <div class="slide-new-info-new-home">
            <section class="server-news-section-new">
                <div class="container">
                    <div class="new-tabhome-inforight d-flex j-center p-relative">
                        <div class="f-cambria server-and-news  p-relative">
                            <div class="svinfo-right server-and-news t-center">
                                <div class="title-svinfo-right p-relative f-utm_nyala d-flex a-center j-center">
                                    <div class="dot-svinfo"></div>
                                    <div class="t-upper">Thông Tin Máy Chủ</div>
                                    <div class="dot-svinfo"></div>
                                </div>
                                <div class="list-svinfo server-and-news">
                                    <?php if ($serverInfo): ?>
                                    <div class="server-info-text t-center" style="white-space: pre-line; color: #e8c088; padding: 0px 20px; line-height: 1.8;">
                                        <?php echo nl2br(htmlspecialchars($serverInfo)); ?>
                                    </div>
                                    <?php else: ?>
                                    <div style="color: #87ceeb; padding: 20px; text-align: center;">
                                        Chưa có thông tin server
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- <div class="join-socialright t-center">
                                <div class="title-jsocialright">- Tham Gia Cùng Chúng Tôi -</div>
                                <div class="list-social-right d-flex a-center j-center">
                                    <?php if (isset($socialLinks['facebook'])): ?>
                                    <a href="<?php echo htmlspecialchars($socialLinks['facebook']['url']); ?>" target="_blank" class="link-socialright d-flex a-center j-center">
                                        <img src="assets/images/icons/fb2.png" alt="">
                                    </a>
                                    <?php endif; ?>
                                    <?php if (isset($socialLinks['discord'])): ?>
                                    <a href="<?php echo htmlspecialchars($socialLinks['discord']['url']); ?>" target="_blank" class="link-socialright d-flex a-center j-center">
                                        <img src="assets/images/icons/discord2.png" alt="">
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div> -->
                        </div>

                        <div class="new-tab-home p-relative right-news">
                            <div class="tab-new-nh f-utm_nyala t-upper d-flex a-center">
                                <div class="item-tab-new-nh c-pointer active" data-new="1">Tất Cả</div>
                                <div class="item-tab-new-nh c-pointer" data-new="2">Tin Nóng</div>
                                <div class="item-tab-new-nh c-pointer" data-new="3">Sự Kiện</div>
                                <div class="item-tab-new-nh c-pointer" data-new="4">Cập Nhật</div>
                            </div>
                            <div class="list-new-detail-nh">
                                <div class="dt-new-nh active" id="new1">
                                    <div class="list-item-news">
                                        <?php foreach ($newsAll as $news): ?>
                                        <a href="<?php echo htmlspecialchars($news['LinkURL']); ?>" class="item-news d-flex a-center f-cambria">
                                            <div class="cat">[<?php echo htmlspecialchars($news['Category']); ?>]</div>
                                            <div class="title-news"><?php echo htmlspecialchars($news['Title']); ?></div>
                                            <div class="date-news hidden-1199"><?php echo date('d/m', strtotime($news['CreatedDate'])); ?></div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="dt-new-nh" id="new2">
                                    <div class="list-item-news">
                                        <?php foreach ($newsHot as $news): ?>
                                        <a href="<?php echo htmlspecialchars($news['LinkURL']); ?>" class="item-news d-flex a-center f-cambria">
                                            <div class="cat">[<?php echo htmlspecialchars($news['Category']); ?>]</div>
                                            <div class="title-news"><?php echo htmlspecialchars($news['Title']); ?></div>
                                            <div class="date-news hidden-1199"><?php echo date('d/m', strtotime($news['CreatedDate'])); ?></div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="dt-new-nh" id="new3">
                                    <div class="list-item-news">
                                        <?php foreach ($newsEvent as $news): ?>
                                        <a href="<?php echo htmlspecialchars($news['LinkURL']); ?>" class="item-news d-flex a-center f-cambria">
                                            <div class="cat">[<?php echo htmlspecialchars($news['Category']); ?>]</div>
                                            <div class="title-news"><?php echo htmlspecialchars($news['Title']); ?></div>
                                            <div class="date-news hidden-1199"><?php echo date('d/m', strtotime($news['CreatedDate'])); ?></div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="dt-new-nh" id="new4">
                                    <div class="list-item-news">
                                        <?php foreach ($newsUpdate as $news): ?>
                                        <a href="<?php echo htmlspecialchars($news['LinkURL']); ?>" class="item-news d-flex a-center f-cambria">
                                            <div class="cat">[<?php echo htmlspecialchars($news['Category']); ?>]</div>
                                            <div class="title-news"><?php echo htmlspecialchars($news['Title']); ?></div>
                                            <div class="date-news hidden-1199"><?php echo date('d/m', strtotime($news['CreatedDate'])); ?></div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="new-home">
            <div class="title-frame t-center t-upper d-flex a-center j-center">
                <img src="assets/images/title/img-title3860.png" alt="">
                <div class="name-title vi">Fanpage</div>
                <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
            </div>

            <div class="slide-new-info-new-home">
                <section class="final-section-new">
                    <div class="container">
                <div class="final-section-grid-new" id="social-links">
                    <div class="fanpage-box-new">
                        <div class="fanpage-header-new">
                            <div class="fanpage-title-new t-upper f-utm_nyala t-center">
                                Fanpage Box
                            </div>
                        </div>
                        <div class="fanpage-content-new">
                            <div class="fanpage-links-new">
                                <?php if (isset($socialLinks['facebook'])): ?>
                                <a href="<?php echo htmlspecialchars($socialLinks['facebook']['url']); ?>" target="_blank" class="fanpage-link-item-new d-flex a-center j-center">
                                    <img src="assets/images/icons/fb2.png" alt="Facebook">
                                    <span><?php echo htmlspecialchars($socialLinks['facebook']['name'] ?: 'Facebook Page'); ?></span>
                                </a>
                                <?php endif; ?>
                                <?php if (isset($socialLinks['facebook_group'])): ?>
                                <a href="<?php echo htmlspecialchars($socialLinks['facebook_group']['url']); ?>" target="_blank" class="fanpage-link-item-new d-flex a-center j-center">
                                    <img src="assets/images/icons/fb2.png" alt="Facebook Group">
                                    <span><?php echo htmlspecialchars($socialLinks['facebook_group']['name'] ?: 'Facebook Group'); ?></span>
                                </a>
                                <?php endif; ?>
                                <?php if (isset($socialLinks['discord'])): ?>
                                <a href="<?php echo htmlspecialchars($socialLinks['discord']['url']); ?>" target="_blank" class="fanpage-link-item-new d-flex a-center j-center">
                                    <img src="assets/images/icons/discord2.png" alt="Discord">
                                    <span><?php echo htmlspecialchars($socialLinks['discord']['name'] ?: 'Discord'); ?></span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="job-system-mini-new">
                        <div class="jobsystem-mini-wrapper">
                            <div class="bg-main-job-mini p-relative t-center">
                                <img src="assets/images/job/img-behind.png" class="img-job-behind-mini">
                                <div class="main-job-chienma-mini f-assassin t-upper t-center d-flex a-center j-center p-absolute">
                                    <div class="it-job-cma-mini it-job-cma1-mini c-pointer p-relative" data-speed="5"
                                        data-src="assets/images/job/img-pop1.jpg" data-name="Thợ Săn"
                                        data-des1="Người bảo vệ thương nhân, giữ họ an toàn khỏi kẻ trộm."
                                        data-des2="Thợ săn kiếm điểm bằng cách tiêu diệt kẻ trộm và giúp đỡ thương nhân trong hành trình buôn bán. Thường bắt đầu nghề từ cấp 60 vì cần đủ mạnh để chiến đấu với kẻ trộm.">
                                        <img src="assets/images/job/img-hunter.png" class="img-cmald-mini">
                                        <div class="name-job-cma-mini d-flex a-center j-center p-absolute">Thợ Săn</div>
                                    </div>
                                    
                                    <div class="it-job-cma-mini it-job-cma2-mini c-pointer p-relative" data-speed="12"
                                        data-src="assets/images/job/img-trader.png" data-name="Thương Nhân"
                                        data-des1="Kiếm lợi nhuận từ việc buôn bán hàng hóa trên Song Long Tranh Bá."
                                        data-des2="Mục tiêu chính là tham gia buôn bán và thuê thợ săn để bảo vệ hàng hóa. Nếu thành công, cả thương nhân và thợ săn đều nhận phần thưởng lớn. Nếu bị kẻ trộm tấn công, có thể mất tất cả nhưng vẫn có cơ hội đoạt lại.">
                                        <img src="assets/images/job/img-trader.png" class="img-cmald-mini">
                                        <div class="name-job-cma-mini d-flex a-center j-center p-absolute">Thương Nhân</div>
                                    </div>
                                    
                                    <div class="it-job-cma-mini it-job-cma3-mini c-pointer p-relative" data-speed="8"
                                        data-src="assets/images/job/img-thief.png" data-name="Kẻ Trộm"
                                        data-des1="Sử dụng vũ lực để đánh bại thợ săn và cướp hàng hóa từ thương nhân."
                                        data-des2="Cướp đoàn buôn của thương nhân và lấy hàng hóa độc đáo. Phải cảnh giác với đòn phản công của thợ săn. Kẻ trộm có thể bán vật phẩm đặc biệt ở bất kỳ thành phố nào.">
                                        <img src="assets/images/job/img-thief.png" class="img-cmald-mini">
                                        <div class="name-job-cma-mini d-flex a-center j-center p-absolute">Kẻ Trộm</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="guide-new-home d-flex a-center j-center m-auto p-relative">
            <a href="#gameplay" class="item-guide-nh d-flex a-center">
                <div class="img-left p-relative">
                    <img src="assets/images/guide/g1.png" class="img-left-nm">
                    <img src="assets/images/guide/g1-hover.png" class="img-left-hv p-absolute">
                </div>
                <div class="name-item f-cambria t-upper">
                    <div>Thông Tin</div>
                    <div>Cập Nhật</div>
                </div>
            </a>
            <a href="#" class="item-guide-nh d-flex a-center">
                <div class="img-left p-relative">
                    <img src="assets/images/guide/g2.png" class="img-left-nm">
                    <img src="assets/images/guide/g2-hover.png" class="img-left-hv p-absolute">
                </div>
                <div class="name-item f-cambria t-upper">
                    <div>Hướng Dẫn</div>
                    <div>Nạp Tiền</div>
                </div>
            </a>
            <a href="#" class="item-guide-nh d-flex a-center">
                <div class="img-left p-relative">
                    <img src="assets/images/guide/g3.png" class="img-left-nm">
                    <img src="assets/images/guide/g3-hover.png" class="img-left-hv p-absolute">
                </div>
                <div class="name-item f-cambria t-upper">
                    <div>Hướng Dẫn</div>
                    <div>Cơ Bản</div>
                </div>
            </a>
            <a href="#job" class="item-guide-nh d-flex a-center">
                <div class="img-left p-relative">
                    <img src="assets/images/guide/g1.png" class="img-left-nm">
                    <img src="assets/images/guide/g1-hover.png" class="img-left-hv p-absolute">
                </div>
                <div class="name-item f-cambria t-upper">
                    <div>Hệ Thống</div>
                    <div>Việc Làm</div>
                </div>
            </a>
        </div>
    </div>

    <div class="foot-frame footer-session d-flex a-center j-center">
    Bản Quyền Thuộc về Song Long Tranh Bá 
    </div>

    <div class="nav-right hidden-1199 open">
        <div class="t-center">
            <img src="assets/images/icon-game.png" class="img-icongame" alt="">
        </div>
        <div class="name-game-nr f-utm_nyala t-center t-upper">Song Long Tranh Bá</div>

        <div class="link-sp-topup t-center t-upper">
            <a href="login.php" class="hover-zoom t-upper t-center d-flex a-center j-center m-auto btn-orange">Đăng Nhập</a>
            <a href="register.php" class="hover-zoom t-upper t-center d-flex a-center j-center m-auto btn-yellow">Đăng Ký</a>
            <a href="ranking.php" class="hover-zoom t-upper t-center d-flex a-center j-center m-auto btn-orange">Xếp Hạng</a>
            <a href="download.php" class="hover-zoom t-upper t-center d-flex a-center j-center m-auto btn-dl-navright">Tải Game</a>
        </div>

        <div class="list-social-right p-absolute">
            <a target="_blank" href="https://www.facebook.com/SROOriginMobile" class="t-upper t-center d-flex a-center j-center m-auto">
                <img class="hover-zoom" src="assets/images/icons/fb.png" alt="">
            </a>
            <a target="_blank" href="https://discord.gg/vRzYDVcDvN" class="t-upper t-center d-flex a-center j-center m-auto">
                <img class="hover-zoom" src="assets/images/icons/discord.png" alt="">
            </a>
        </div>

        <span class="i-control text-center f-utm_cafeta i-control-open">
            <span class="i-txt"></span>
        </span>
    </div>

    <div class="popup" id="popup-job" style="display: none;">
        <a class="close-popup-full"></a>
        <div class="content-popup">
            <div class="wrapper-popup">
                <div class="inside-wp m-auto p-relative">
                    <div class="main-pop-job">
                        <div class="img-pop-job">
                            <img src="assets/images/job/img-pop1.jpg" alt="" class="">
                        </div>
                        <div class="info-pop-job f-calibri">
                            <div class="name-big1 f-cambriaB t-upper">Thợ Săn</div>
                            <div class="des-big1">Người bảo vệ thương nhân.</div>
                            <div class="line-pop-job t-center">
                                <img src="assets/images/popup/line.png" alt="">
                            </div>
                            <div class="name-big2 f-cambriaB">Gameplay</div>
                            <div class="des-big2">Mô tả chi tiết</div>
                        </div>
                    </div>
                </div>
                <a class="close-popup"></a>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="assets/js/swiper-bundle.min.js"></script>
    <script type="text/javascript" src="assets/js/vendor.js"></script>
    <script type="text/javascript" src="assets/js/app.js"></script>

    <script>
        // Lucky Wheel Ticker - Auto-refresh (optional, data already loaded from server)
        function refreshLuckyWheelTicker() {
            $.ajax({
                url: '/api/lucky_wheel/get_recent_rare_wins.php?limit=20',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    const tickerContent = $('#tickerContent');
                    if (tickerContent.length === 0) return;
                    
                    tickerContent.empty();
                    
                    if (response.success && response.data && response.data.length > 0) {
                        const scrollDiv = $('<div>').addClass('ticker-scroll');
                        
                        // Duplicate items for seamless loop
                        const items = [...response.data, ...response.data];
                        
                        items.forEach(function(win) {
                            const timeAgo = formatTimeAgo(win.won_date_iso || win.won_date);
                            const item = $('<div>')
                                .addClass('ticker-item')
                                .html(
                                    '<span class="username">' + escapeHtml(win.username) + '</span>' +
                                    '<span class="separator">đã trúng</span>' +
                                    '<span class="item-name">' + escapeHtml(win.item_name) + '</span>' +
                                    '<span class="time-ago">(' + timeAgo + ')</span>'
                                );
                            scrollDiv.append(item);
                        });
                        
                        tickerContent.append(scrollDiv);
                    } else {
                        tickerContent.html('<div class="ticker-item">Chưa có người chơi nào trúng vật phẩm hiếm</div>');
                    }
                },
                error: function() {
                    // Silently fail on refresh, keep existing content
                }
            });
        }
        
        // Format time ago (X phút/giờ/ngày trước) - for auto-refresh
        function formatTimeAgo(dateString) {
            if (!dateString) return '';
            
            const now = new Date();
            // Prefer ISO-8601 from API. If server returns "YYYY-MM-DD HH:mm:ss.mmm",
            // normalize to "YYYY-MM-DDTHH:mm:ss.mmm" to improve parsing reliability.
            const normalized = typeof dateString === 'string' ? dateString.replace(' ', 'T') : dateString;
            const date = new Date(normalized);
            const diffMs = now - date;
            const diffSecs = Math.floor(diffMs / 1000);
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) {
                return 'vừa xong';
            } else if (diffMins < 60) {
                return diffMins + ' phút trước';
            } else if (diffHours < 24) {
                return diffHours + ' giờ trước';
            } else if (diffDays < 7) {
                return diffDays + ' ngày trước';
            } else {
                // Format date if more than 7 days
                const day = date.getDate();
                const month = date.getMonth() + 1;
                const year = date.getFullYear();
                return day + '/' + month + (year !== now.getFullYear() ? '/' + year : '');
            }
        }
        
        // Escape HTML - for auto-refresh
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
        
        // Auto-refresh ticker every 60 seconds (optional)
        $(document).ready(function() {
            // Auto-refresh every 60 seconds
            setInterval(refreshLuckyWheelTicker, 180000);
        });
        
        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.hash);
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800);
            }
        });

        // News tabs
        $('.item-tab-new-nh').click(function() {
            let _id = $(this).attr("data-new");
            $('.item-tab-new-nh').removeClass('active');
            $(this).addClass('active');
            $('.dt-new-nh').removeClass('active');
            $('#new' + _id).addClass('active');
        });

        // News slider (using slick from vendor.js)
        if (typeof $.fn.slick !== 'undefined') {
            $('.slide-new-home').slick({
                infinite: true,
                dots: true,
                arrows: false,
                autoplay: true,
                autoplaySpeed: 3000
            });
        }

        // Gameplay Swiper
        var gamePlaySlide = new Swiper(".list-gpl", {
            autoHeight: false,
            slidesPerView: 2,
            spaceBetween: 10,
            mousewheel: false,
            navigation: {
                nextEl: ".next-slide-gpl",
                prevEl: ".prev-slide-gpl",
            },
            breakpoints: {
                768: {
                    slidesPerView: 3,
                    spaceBetween: 15,
                },
                1200: {
                    slidesPerView: 4,
                    spaceBetween: 20,
                },
                1600: {
                    slidesPerView: 5,
                    spaceBetween: 25,
                },
            },
        });

        // Job hover parallax effect
        window.addEventListener('DOMContentLoaded', function() {
            const parallaxElements = document.querySelectorAll('.it-job-cma');

            parallaxElements.forEach(function(element) {
                element.addEventListener('mouseenter', function() {
                    element.style.transition = 'transform 0.3s ease';
                });

                element.addEventListener('mouseleave', function() {
                    element.style.transition = 'none';
                });

                element.addEventListener('mousemove', function(e) {
                    const mouseX = e.clientX;
                    const mouseY = e.clientY;
                    const speed = parseFloat(element.getAttribute('data-speed'));
                    const width = window.innerWidth / 2;
                    const height = window.innerHeight / 2;
                    const offsetX = width - mouseX;
                    const offsetY = height - mouseY;
                    const translateX = offsetX / width * speed;
                    const translateY = offsetY / height * speed;
                    element.style.transform = `translate(${translateX}px, ${translateY}px)`;
                });
            });
        });

        // Job popup
        $(document).ready(function() {
            $("body").on("click", ".it-job-cma", function() {
                let _src = $(this).data("src");
                let _name = $(this).data("name");
                let _des1 = $(this).data("des1");
                let _des2 = $(this).data("des2");

                $("#popup-job .img-pop-job img").attr('src', _src);
                $("#popup-job .name-big1").html(_name);
                $("#popup-job .des-big1").html(_des1);
                $("#popup-job .des-big2").html(_des2);
                $("#popup-job").show();
            });

            $("body").on("click", ".close-popup, .close-popup-full", function() {
                $(this).parents(".popup").hide();
            });

            // Mobile menu toggle
            $('.i-control').click(function() {
                $('.nav-right').toggleClass('open');
                $(this).toggleClass('i-control-open');
            });

            // Hamburger menu
            $('#toggle-hambuger').change(function() {
                if($(this).is(':checked')) {
                    $('.navbar-content').addClass('active');
                } else {
                    $('.navbar-content').removeClass('active');
                }
            });
            
            // Load leaderboard automatically on page load
            loadHomeLeaderboard();
            
            // Auto-refresh leaderboard every 60 seconds
            setInterval(function() {
                loadHomeLeaderboard();
            }, 60000);
        });
        
        // Load home leaderboard automatically on page load
        function loadHomeLeaderboard() {
            $.ajax({
                url: '/api/lucky_wheel/get_leaderboard.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayHomeLeaderboard(response.data);
                    } else {
                        $('#homeLeaderboardContent').html('<div style="text-align: center; color: #87ceeb; padding: 20px;">Không có dữ liệu</div>');
                    }
                },
                error: function() {
                    $('#homeLeaderboardContent').html('<div style="text-align: center; color: #87ceeb; padding: 20px;">Lỗi tải dữ liệu</div>');
                }
            });
        }
        
        // Display home leaderboard
        function displayHomeLeaderboard(data) {
            const leaderboard = data.leaderboard || [];
            const season = data.season;
            
            if (leaderboard.length === 0) {
                $('#homeLeaderboardContent').html('<div style="text-align: center; color: #87ceeb; padding: 20px;">Chưa có dữ liệu</div>');
                return;
            }
            
            let html = '<div class="home-leaderboard-list">';
            
            leaderboard.forEach(function(player, index) {
                const rankClass = 'rank-' + player.rank;
                html += `
                    <div class="home-leaderboard-item ${rankClass}">
                        <span class="home-leaderboard-rank">${index + 1}</span>
                        <span class="home-leaderboard-username">${escapeHtml(player.username)}</span>
                        <span class="home-leaderboard-spins">${player.total_spins.toLocaleString()}</span>
                    </div>
                `;
            });
            
            html += '</div>';
            
            if (season) {
                html += `<div class="home-leaderboard-season">Mùa: ${escapeHtml(season.name)}</div>`;
            }
            
            $('#homeLeaderboardContent').html(html);
        }
        
        // Escape HTML helper (if not already defined)
        if (typeof escapeHtml === 'undefined') {
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }
        }
    </script>
</body>
</html>
