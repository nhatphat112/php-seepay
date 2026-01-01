<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Con Đường Tơ Lụa - Silkroad Origin Mobile</title>
    <meta name="description" content="Trang web chính thức của Con Đường Tơ Lụa Mobile. MMORPG huyền thoại giờ đây đã có mặt trên di động."/>
    <meta name="keywords" content="Silkroad, MMORPG, Con đường tơ lụa, Game online, Free game"/>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico"/>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/vendor.css" />
    <link rel="stylesheet" href="assets/css/main1bce.css?v=6" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/font-override.css" />
    
    <style>
        /* Job Menu Item - Push to left */
        .job-menu-item {
            margin-left: 340px !important;
        }
        
        @media (max-width: 1199px) {
            .job-menu-item {
                margin-left: 0 !important;
            }
        }
        
        /* Auth Buttons in Header */
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
        
        @media (max-width: 1199px) {
            .auth-links {
                display: none !important;
            }
        }
    </style>
    
    <!-- jQuery -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
</head>

<body class="home-page">

    <!-- Header -->
    <div class="header">
        <input id="toggle-hambuger" type="checkbox" style="display:none">
        <nav class="navbar clearfix">
            <div class="container">
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="Silkroad Logo" class="logo-top hidden-1199">
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
                                <a target="_blank" href="https://www.facebook.com/SROOriginMobile">Facebook</a>
                                <a target="_blank" href="https://www.facebook.com/groups/srooriginm">Group Facebook</a>
                                <a target="_blank" href="https://discord.gg/vRzYDVcDvN">Discord</a>
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
                        <li class=""><a href="#news" class="a100">Tin Tức</a></li>
                        <li class=""><a href="#gameplay" class="a100">Gameplay</a></li>
                        <li class="job-menu-item"><a href="#job" class="a100">Hệ Thống Việc Làm</a></li>
                        <li class="hidden-1199 link-show-com-pc p-relative">
                            <a class="a100" href="javascript:;">
                                <div class="text-show-com-pc">Cộng Đồng</div>
                            </a>
                            <div class="list-link-show-com p-absolute">
                                <a target="_blank" href="https://www.facebook.com/SROOriginMobile">Facebook</a>
                                <a target="_blank" href="https://www.facebook.com/groups/srooriginm">Group Facebook</a>
                                <a target="_blank" href="https://discord.gg/vRzYDVcDvN">Discord</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <!-- Hero Section -->
    <div class="info-top">
        <div class="slogan-link-to-top t-center">
            <div class="list-bestgame d-flex a-center j-center">
                <img src="assets/images/bestgame/b1.png" alt="" class="img-bestgame">
                <img src="assets/images/bestgame/b2.png" alt="" class="img-bestgame">
                <img src="assets/images/bestgame/b3.png" alt="" class="img-bestgame">
            </div>

            <div class="text-slogan t-upper t-center">Huyền Thoại Tái Sinh, Vinh Quang Trở Lại</div>
            
            <div class="list-link-top d-flex a-center j-center f-utm_nyala t-upper">
                <a class="a-link-topinfo hover-zoom d-flex a-center j-center btn-top" href="#news">Tin Tức</a>
                <img src="assets/images/infotop/img-x.png" alt="" class="img-xlinktop">
                <a class="a-link-topinfo hover-zoom d-flex a-center j-center btn-top" href="#gameplay">Hướng Dẫn</a>
                <img src="assets/images/infotop/img-x.png" alt="" class="img-xlinktop">
                <a class="a-link-topinfo hover-zoom d-flex a-center j-center btn-top" href="#job">Hệ Thống Job</a>
            </div>
        </div>

        <div class="download-link-preregister t-center" style="margin-top: 3%;">
            <a href="download.php" class="link-to-dlnow t-upper">Tải Game Ngay</a>
        </div>

        <div class="wemademax t-upper f-calibri d-flex a-center j-center">
            <span>-</span>
            <span>Hợp tác với:</span>
            <img src="assets/images/wemade_bk3860.png" class="logo-wemade" style="filter: brightness(0) invert(1);">
            <span>-</span>
        </div>
    </div>

    <!-- Hot News Section -->
    <div class="new-home" id="news">
        <div class="title-frame t-center t-upper d-flex a-center j-center">
            <img src="assets/images/title/img-title3860.png" alt="">
            <div class="name-title vi">Tin Tức Nóng</div>
            <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
        </div>

        <div class="slide-new-info-new-home m-auto">
            <div class="slide-hotevent p-relative">
                <div class="slide-new-home slick-custom-dots">
                    <a href="#" class="item-slide-nh">
                        <img src="images/image-1.jpg" alt="News 1">
                    </a>
                    <a href="#" class="item-slide-nh">
                        <img src="images/image-2.jpg" alt="News 2">
                    </a>
                    <a href="#" class="item-slide-nh">
                        <img src="images/image-3.jpg" alt="News 3">
                    </a>
                </div>

                <div class="list-hotevent d-flex a-center j-center">
                    <div class="title-hotevent d-flex a-center">
                        <img src="assets/images/box-new/img-hot-events.png" alt="">
                        <div class="name-title-hotevent f-utm_nyala t-upper">
                            <div>
                                <div class="">Lịch Trình</div>
                                <div class="">Sự Kiện</div>
                            </div>
                            <div style="font-size: clamp(6px, 1vw, 12px);">(GMT +7)</div>
                        </div>
                    </div>
                    <div class="list-item-hotevent p-relative f-cambria d-flex a-center j-center t-center">
                        <img src="assets/images/box-new/line-hotevents.png" class="line-hev p-absolute">
                        
                        <div class="item-hotevent">
                            <div class="time-day-hev">
                                <div class="time-hev">19:30</div>
                                <div class="day-hev">Thứ 2-4-6</div>
                            </div>
                            <div class="dot-hev">
                                <img src="assets/images/box-new/dot-hot-event.png" alt="">
                            </div>
                            <div class="title-hev">Boss Bang Hội</div>
                        </div>

                        <div class="item-hotevent">
                            <div class="time-day-hev">
                                <div class="time-hev">19:30</div>
                                <div class="day-hev">Thứ 3-5-7</div>
                            </div>
                            <div class="dot-hev">
                                <img src="assets/images/box-new/dot-hot-event.png" alt="">
                            </div>
                            <div class="title-hev">Đấu Trường Bang</div>
                        </div>

                        <div class="item-hotevent">
                            <div class="time-day-hev">
                                <div class="time-hev">11:00 - 21:00</div>
                                <div class="day-hev">Hàng Ngày</div>
                            </div>
                            <div class="dot-hev">
                                <img src="assets/images/box-new/dot-hot-event.png" alt="">
                            </div>
                            <div class="title-hev">Buôn Bán</div>
                        </div>
                        
                        <div class="item-hotevent">
                            <div class="time-day-hev">
                                <div class="time-hev">15:30 - 19:00</div>
                                <div class="day-hev">Hàng Ngày</div>
                            </div>
                            <div class="dot-hev">
                                <img src="assets/images/box-new/dot-hot-event.png" alt="">
                            </div>
                            <div class="title-hev">Boss Độc Nhất</div>
                        </div>

                        <div class="item-hotevent">
                            <div class="time-day-hev">
                                <div class="time-hev">20:30</div>
                                <div class="day-hev">Hàng Ngày</div>
                            </div>
                            <div class="dot-hev">
                                <img src="assets/images/box-new/dot-hot-event.png" alt="">
                            </div>
                            <div class="title-hev">Chiến Trường</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="new-tabhome-inforight d-flex j-center p-relative">
                <div class="new-tab-home p-relative">
                    <div class="tab-new-nh f-utm_nyala t-upper d-flex a-center">
                        <div class="item-tab-new-nh c-pointer active" data-new="1">Tất Cả</div>
                        <div class="item-tab-new-nh c-pointer" data-new="2">Tin Nóng</div>
                        <div class="item-tab-new-nh c-pointer" data-new="3">Sự Kiện</div>
                        <div class="item-tab-new-nh c-pointer" data-new="4">Cập Nhật</div>
                    </div>
                    <div class="list-new-detail-nh">
                        <div class="dt-new-nh active" id="new1">
                            <div class="list-item-news">
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Sự Kiện]</div>
                                    <div class="title-news">Sự Kiện Đăng Nhập Nhận Quà</div>
                                    <div class="date-news hidden-1199">02/10</div>
                                </a>
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Cập Nhật]</div>
                                    <div class="title-news">Cập Nhật Phiên Bản 2.1.0</div>
                                    <div class="date-news hidden-1199">01/10</div>
                                </a>
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Tin Tức]</div>
                                    <div class="title-news">Hướng Dẫn Tân Thủ</div>
                                    <div class="date-news hidden-1199">30/09</div>
                                </a>
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Sự Kiện]</div>
                                    <div class="title-news">Săn Boss Nhận Thưởng Lớn</div>
                                    <div class="date-news hidden-1199">29/09</div>
                                </a>
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Cập Nhật]</div>
                                    <div class="title-news">Tối Ưu Hệ Thống PvP</div>
                                    <div class="date-news hidden-1199">28/09</div>
                                </a>
                            </div>
                        </div>
                        <div class="dt-new-nh" id="new2">
                            <div class="list-item-news">
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Tin Tức]</div>
                                    <div class="title-news">Hướng Dẫn Tân Thủ</div>
                                    <div class="date-news hidden-1199">30/09</div>
                                </a>
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Tin Tức]</div>
                                    <div class="title-news">Top Game Hay Tháng 9</div>
                                    <div class="date-news hidden-1199">25/09</div>
                                </a>
                            </div>
                        </div>
                        <div class="dt-new-nh" id="new3">
                            <div class="list-item-news">
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Sự Kiện]</div>
                                    <div class="title-news">Sự Kiện Đăng Nhập Nhận Quà</div>
                                    <div class="date-news hidden-1199">02/10</div>
                                </a>
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Sự Kiện]</div>
                                    <div class="title-news">Săn Boss Nhận Thưởng Lớn</div>
                                    <div class="date-news hidden-1199">29/09</div>
                                </a>
                            </div>
                        </div>
                        <div class="dt-new-nh" id="new4">
                            <div class="list-item-news">
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Cập Nhật]</div>
                                    <div class="title-news">Cập Nhật Phiên Bản 2.1.0</div>
                                    <div class="date-news hidden-1199">01/10</div>
                                </a>
                                <a href="#" class="item-news d-flex a-center f-cambria">
                                    <div class="cat">[Cập Nhật]</div>
                                    <div class="title-news">Tối Ưu Hệ Thống PvP</div>
                                    <div class="date-news hidden-1199">28/09</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sv-info-social-nh f-cambria p-relative">
                    <div class="svinfo-right t-center">
                        <div class="title-svinfo-right p-relative f-utm_nyala d-flex a-center j-center">
                            <div class="dot-svinfo"></div>
                            <div class="t-upper">Thông Tin Máy Chủ</div>
                            <div class="dot-svinfo"></div>
                        </div>
                        <div class="list-svinfo">
                            <div class="item-svinfo d-flex a-center j-center">
                                <div class="if-left-svinfo">Cấp Độ,</div>
                                <div class="if-right-svinfo p-relative">100</div>
                            </div>
                            <div class="item-svinfo d-flex a-center j-center">
                                <div class="if-left-svinfo">Chủng Tộc,</div>
                                <div class="if-right-svinfo p-relative">Á Châu - Âu Châu</div>
                            </div>
                            <div class="item-svinfo d-flex a-center j-center">
                                <div class="if-left-svinfo">Kỹ Năng</div>
                                <div class="if-right-svinfo p-relative">300</div>
                            </div>
                            <div class="item-svinfo d-flex a-center j-center">
                                <div class="if-left-svinfo">Trang Bị</div>
                                <div class="if-right-svinfo p-relative">10 Đẳng</div>
                            </div>
                            <div class="item-svinfo d-flex a-center j-center">
                                <div class="if-left-svinfo">Rare</div>
                                <div class="if-right-svinfo p-relative">SUN</div>
                            </div>
                            <div class="item-svinfo d-center j-center">
                                <div class="if-left-svinfo">Tơ Lụa</div>
                                <div class="if-right-svinfo p-relative">Có Thể Giao Dịch</div>
                            </div>
                            <div class="item-svinfo d-flex a-center j-center">
                                <div class="if-left-svinfo">Vàng</div>
                                <div class="if-right-svinfo p-relative">Có Thể Giao Dịch</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="join-socialright t-center">
                        <div class="title-jsocialright">- Tham Gia Cùng Chúng Tôi -</div>
                        <div class="list-social-right d-flex a-center j-center">
                            <a href="https://www.facebook.com/SROOriginMobile" target="_blank" class="link-socialright d-flex a-center j-center">
                                <img src="assets/images/icons/fb2.png" alt="">
                            </a>
                            <a href="https://discord.gg/vRzYDVcDvN" target="_blank" class="link-socialright d-flex a-center j-center">
                                <img src="assets/images/icons/discord2.png" alt="">
                            </a>
                        </div>
                    </div>
                </div>
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

    <!-- Gameplay Section -->
    <div class="gameplay" id="gameplay">
        <div class="title-frame t-center t-upper d-flex a-center j-center">
            <img src="assets/images/title/img-title3860.png" alt="">
            <div class="name-title vi">Gameplay</div>
            <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
        </div>

        <div class="main-gameplay-out p-relative">
            <div class="prev-next-slide-gpl d-flex a-center j-center c-pointer prev-slide-gpjob prev-slide-gpl cursor-hover-item">
                <img src="assets/images/prev-big.png" alt="">
            </div>

            <div class="main-gameplay list-gameplay list-gpl swiper d-flex a-center">
                <div class="swiper-wrapper">
                    <div class="it-gpimg swiper-slide it-gpimg1 f-calibri c-pointer" style="--bg:#181613">
                        <div class="img-gpl">
                            <img src="assets/images/gameplay/gp1.jpg" alt="Leveling">
                        </div>
                        <div class="name-des-gpl">
                            <div class="name-gpl f-cambriaB">Lên Cấp</div>
                            <div class="line-namedes-gpl">
                                <img src="assets/images/gameplay/line.png" alt="">
                            </div>
                            <div class="des-gpl">Nhiệm vụ quan trọng nhất bạn cần làm trong MMORPG.</div>
                        </div>
                    </div>

                    <div class="it-gpimg swiper-slide it-gpimg2 f-calibri c-pointer" style="--bg:#13191a">
                        <div class="img-gpl">
                            <img src="assets/images/gameplay/gp2.jpg" alt="Skill Mastery">
                        </div>
                        <div class="name-des-gpl">
                            <div class="name-gpl f-cambriaB">Kỹ Năng</div>
                            <div class="line-namedes-gpl">
                                <img src="assets/images/gameplay/line.png" alt="">
                            </div>
                            <div class="des-gpl">Phát triển hệ thống kỹ năng trong giới hạn cấp độ kỹ năng.</div>
                        </div>
                    </div>

                    <div class="it-gpimg swiper-slide it-gpimg3 f-calibri c-pointer" style="--bg:#181613">
                        <div class="img-gpl">
                            <img src="assets/images/gameplay/gp3.jpg" alt="Gear Up">
                        </div>
                        <div class="name-des-gpl">
                            <div class="name-gpl f-cambriaB">Trang Bị</div>
                            <div class="line-namedes-gpl">
                                <img src="assets/images/gameplay/line.png" alt="">
                            </div>
                            <div class="des-gpl">Nâng cấp nhân vật và trang bị vật phẩm mới để tăng sức mạnh.</div>
                        </div>
                    </div>

                    <div class="it-gpimg swiper-slide it-gpimg4 f-calibri c-pointer" style="--bg:#26221d">
                        <div class="img-gpl">
                            <img src="img/gp4.jpg" alt="Alchemy">
                        </div>
                        <div class="name-des-gpl">
                            <div class="name-gpl f-cambriaB">Luyện Kim</div>
                            <div class="line-namedes-gpl">
                                <img src="assets/images/gameplay/line.png" alt="">
                            </div>
                            <div class="des-gpl">Chế tạo và nâng cấp trang bị để tăng sức mạnh chiến đấu.</div>
                        </div>
                    </div>

                    <div class="it-gpimg swiper-slide it-gpimg5 f-calibri c-pointer" style="--bg:#200d0d">
                        <div class="img-gpl">
                            <img src="img/gp5.jpg" alt="Battle">
                        </div>
                        <div class="name-des-gpl">
                            <div class="name-gpl f-cambriaB">Chiến Đấu</div>
                            <div class="line-namedes-gpl">
                                <img src="assets/images/gameplay/line.png" alt="">
                            </div>
                            <div class="des-gpl">Tham gia các sự kiện lịch sử trên Con Đường Tơ Lụa.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="prev-next-slide-gpl d-flex a-center j-center c-pointer next-slide-gpjob next-slide-gpl cursor-hover-item">
                <img src="assets/images/next-big.png" alt="">
            </div>
        </div>
    </div>

    <!-- Job System Section -->
    <div class="jobsystem" id="job">
        <div class="title-frame t-center t-upper d-flex a-center j-center">
            <img src="assets/images/title/img-title3860.png" alt="">
            <div class="name-title vi">Hệ Thống Việc Làm</div>
            <img src="assets/images/title/img-title3860.png" style="transform: scaleX(-1);">
        </div>

        <div class="bg-main-job p-relative t-center">
            <img src="assets/images/job/img-behind.png" class="img-job-behind">
            <div class="main-job-chienma f-assassin t-upper t-center d-flex a-center j-center p-absolute">
                <div class="it-job-cma it-job-cma1 c-pointer p-relative" data-speed="5"
                    data-src="assets/images/job/img-pop1.jpg" data-name="Thợ Săn"
                    data-des1="Người bảo vệ thương nhân, giữ họ an toàn khỏi kẻ trộm."
                    data-des2="Thợ săn kiếm điểm bằng cách tiêu diệt kẻ trộm và giúp đỡ thương nhân trong hành trình buôn bán. Thường bắt đầu nghề từ cấp 60 vì cần đủ mạnh để chiến đấu với kẻ trộm.">
                    <img src="assets/images/job/img-hunter.png" class="img-cmald">
                    <div class="name-job-cma d-flex a-center j-center p-absolute">Thợ Săn</div>
                </div>
                
                <div class="it-job-cma it-job-cma2 c-pointer p-relative" data-speed="12"
                    data-src="assets/images/job/img-trader.png" data-name="Thương Nhân"
                    data-des1="Kiếm lợi nhuận từ việc buôn bán hàng hóa trên Con Đường Tơ Lụa."
                    data-des2="Mục tiêu chính là tham gia buôn bán và thuê thợ săn để bảo vệ hàng hóa. Nếu thành công, cả thương nhân và thợ săn đều nhận phần thưởng lớn. Nếu bị kẻ trộm tấn công, có thể mất tất cả nhưng vẫn có cơ hội đoạt lại.">
                    <img src="assets/images/job/img-trader.png" class="img-cmald">
                    <div class="name-job-cma d-flex a-center j-center p-absolute">Thương Nhân</div>
                </div>
                
                <div class="it-job-cma it-job-cma3 c-pointer p-relative" data-speed="8"
                    data-src="assets/images/job/img-thief.png" data-name="Kẻ Trộm"
                    data-des1="Sử dụng vũ lực để đánh bại thợ săn và cướp hàng hóa từ thương nhân."
                    data-des2="Cướp đoàn buôn của thương nhân và lấy hàng hóa độc đáo. Phải cảnh giác với đòn phản công của thợ săn. Kẻ trộm có thể bán vật phẩm đặc biệt ở bất kỳ thành phố nào.">
                    <img src="assets/images/job/img-thief.png" class="img-cmald">
                    <div class="name-job-cma d-flex a-center j-center p-absolute">Kẻ Trộm</div>
                </div>
            </div>
        </div>
    </div>


    <div class="foot-frame d-flex a-center j-center">
        <div class="logo-wemade">
            <img src="assets/images/wemade_bk3860.png" alt="" style="filter: brightness(0) invert(1);">
        </div>
        <div class="term-copyright">
            <div class="link-copyright d-flex a-center j-center t-upper">
                <a target="_blank" href="#" class="link-term cursor-hover-item">Điều Khoản Sử Dụng</a>
                <span style="margin: 0 10px;">•</span>
                <a target="_blank" href="#" class="link-term cursor-hover-item">Chính Sách Bảo Mật</a>
            </div>
            <div class="copyright-ft t-center f-calibri">Copyright © Con Đường Tơ Lụa Mobile. All rights reserved.</div>
        </div>
        <div class="logo-gs-gh d-flex a-center j-center">
            <img src="assets/images/wemade_bk3860.png" alt="" style="filter: brightness(0) invert(1); width: 100px;">
        </div>
    </div>

    <!-- Right Navigation (Mobile) -->
    <div class="nav-right hidden-1199 open">
        <div class="t-center">
            <img src="assets/images/icon-game.png" class="img-icongame" alt="">
        </div>
        <div class="name-game-nr f-utm_nyala t-center t-upper">CON ĐƯỜNG TƠ LỤA MOBILE</div>

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

    <!-- Popup Job Details -->
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

    <!-- JavaScript -->
    <script type="text/javascript" src="assets/js/swiper-bundle.min.js"></script>
    <script type="text/javascript" src="assets/js/vendor.js"></script>
    <script type="text/javascript" src="assets/js/app.js"></script>

    <script>
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
        });
    </script>
</body>
</html>
