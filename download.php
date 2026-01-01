<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tải Game - Con Đường Tơ Lụa Mobile</title>
    
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
        
        .download-box {
            width: 100%;
            max-width: 700px;
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
            overflow: visible; /* Đảm bảo header không bị cắt */
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
        
        .download-header {
            text-align: center;
            margin-bottom: 35px;
            padding-top: 20px; /* Thêm padding để tránh bị cắt */
            position: relative;
            z-index: 10;
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
        
        .download-logo {
            margin-bottom: 20px;
        }
        
        .logo-img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .download-logo h1 {
            font-size: 24px;
            color: #ffd700;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .download-header h2 {
            font-size: 26px;
            color: #fff;
            margin: 15px 0 10px;
        }
        
        .download-header p {
            color: #999;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .platform-section {
            margin-bottom: 30px;
        }
        
        .platform-title {
            font-size: 18px;
            color: #ffd700;
            margin-bottom: 20px;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .platform-title i {
            font-size: 20px;
        }
        
        .download-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .download-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .download-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 215, 0, 0.4);
            transform: translateX(5px);
        }
        
        .download-item-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .download-item-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            color: #ffd700;
            font-size: 20px;
        }
        
        .download-item-text {
            flex: 1;
        }
        
        .download-item-text .name {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 3px;
        }
        
        .download-item-text .desc {
            display: block;
            font-size: 12px;
            color: #888;
        }
        
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
        
        .download-btn i {
            font-size: 14px;
        }
        
        .system-requirements {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }
        
        .system-requirements h3 {
            font-size: 16px;
            color: #ffd700;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }
        
        .requirement-item {
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            text-align: center;
        }
        
        .requirement-item .label {
            display: block;
            font-size: 11px;
            color: #888;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .requirement-item .value {
            display: block;
            font-size: 14px;
            color: #fff;
            font-weight: 600;
        }
        
        @media (max-width: 576px) {
            .download-box {
                padding: 30px 20px;
            }
            
            .download-item {
                flex-direction: column;
                text-align: center;
            }
            
            .download-item-info {
                flex-direction: column;
                text-align: center;
            }
            
            .download-btn {
                width: 100%;
                justify-content: center;
            }
            
            .requirements-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    
    <!-- jQuery -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
</head>
<body class="home-page">
    <!-- Download Overlay - Form nổi trên nền trang chính -->
    <div class="auth-overlay">
        <div class="download-box">
            <div class="download-header">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                </a>
                <div class="download-logo">
                    <img src="assets/images/logo.png" alt="Logo" class="logo-img">
                    <h1 class="f-utm_nyala t-upper">Con Đường Tơ Lụa</h1>
                </div>
                <h2 class="f-cambria">Tải Game Ngay</h2>
                <p class="f-calibri">Chọn phiên bản phù hợp và bắt đầu hành trình huyền thoại</p>
            </div>

            <!-- Game Client Downloads -->
            <div class="platform-section">
                <h3 class="platform-title">
                    <i class="fas fa-download"></i> Tải Client Game (PC)
                </h3>
                <div class="download-list">
                    <div class="download-item">
                        <div class="download-item-info">
                            <div class="download-item-icon">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div class="download-item-text">
                                <span class="name">Client Game - Full Version</span>
                                <span class="desc">Phiên bản đầy đủ, cài đặt trực tiếp (~3.5 GB)</span>
                            </div>
                        </div>
                        <a href="#" class="btn-download" onclick="alert('Link tải sẽ được cập nhật sớm!'); return false;">
                            <i class="fas fa-download"></i> Tải Ngay
                        </a>
                    </div>
                    
                    <div class="download-item">
                        <div class="download-item-info">
                            <div class="download-item-icon">
                                <i class="fas fa-cloud-download-alt"></i>
                            </div>
                            <div class="download-item-text">
                                <span class="name">Client Game - Google Drive</span>
                                <span class="desc">Tải từ Google Drive, tốc độ cao (~3.5 GB)</span>
                            </div>
                        </div>
                        <a href="#" class="btn-download" onclick="alert('Link tải sẽ được cập nhật sớm!'); return false;">
                            <i class="fab fa-google-drive"></i> Tải Ngay
                        </a>
                    </div>
                    
                    <div class="download-item">
                        <div class="download-item-info">
                            <div class="download-item-icon">
                                <i class="fas fa-file-archive"></i>
                            </div>
                            <div class="download-item-text">
                                <span class="name">Client Game - Mega.nz</span>
                                <span class="desc">Mirror link dự phòng (~3.5 GB)</span>
                            </div>
                        </div>
                        <a href="#" class="btn-download" onclick="alert('Link tải sẽ được cập nhật sớm!'); return false;">
                            <i class="fas fa-download"></i> Tải Ngay
                        </a>
                    </div>
                </div>
            </div>


            <!-- System Requirements -->
            <div class="system-requirements">
                <h3><i class="fas fa-info-circle"></i> Cấu Hình Khuyến Nghị</h3>
                <div class="requirements-grid">
                    <div class="requirement-item">
                        <span class="label">OS</span>
                        <span class="value">Win 10/11</span>
                    </div>
                    <div class="requirement-item">
                        <span class="label">RAM</span>
                        <span class="value">4 GB+</span>
                    </div>
                    <div class="requirement-item">
                        <span class="label">CPU</span>
                        <span class="value">Core i3+</span>
                    </div>
                    <div class="requirement-item">
                        <span class="label">GPU</span>
                        <span class="value">GT 730+</span>
                    </div>
                    <div class="requirement-item">
                        <span class="label">Dung lượng</span>
                        <span class="value">~5 GB</span>
                    </div>
                    <div class="requirement-item">
                        <span class="label">Internet</span>
                        <span class="value">Ổn định</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Download page loaded - PC version only');
    </script>
</body>
</html>
