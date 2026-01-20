<?php
/**
 * CMS Admin - Lucky Wheel Management
 * Quản lý vòng quay may mắn: bật/tắt, thêm/sửa/xóa vật phẩm
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Vòng Quay May Mắn - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #0f1624;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 600;
            color: #87ceeb;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab:hover {
            color: #e8c088;
        }
        
        .tab.active {
            color: #e8c088;
            border-bottom-color: #e8c088;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .config-section {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            margin-bottom: 20px;
        }
        
        .config-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #0f1624;
        }
        
        .config-row:last-child {
            border-bottom: none;
        }
        
        .config-label {
            color: #e8c088;
            font-weight: 600;
        }
        
        .config-value {
            color: #87ceeb;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #dc3545;
            transition: .4s;
            border-radius: 30px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #28a745;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        .items-table {
            margin-top: 20px;
        }
        
        .rare-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .rare-yes {
            background: #ff6b6b;
            color: #fff;
        }
        
        .rare-no {
            background: #4682b4;
            color: #fff;
        }
        
        .win-rate-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.85rem;
            background: #e8c088;
            color: #1a1a2e;
            font-weight: 600;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            overflow: auto;
        }
        
        .modal-content {
            background: #16213e;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #0f1624;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #0f1624;
        }
        
        .modal-header h3 {
            color: #e8c088;
            margin: 0;
        }
        
        .close {
            color: #87ceeb;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #e8c088;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-cog"></i> CMS Dashboard</h1>
                <p>Quản lý nội dung</p>
            </div>
            <ul class="nav-menu">
                <li><a href="cms/"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="slider.php"><i class="fas fa-images"></i> Slider (5 ảnh)</a></li>
                <li><a href="news.php"><i class="fas fa-newspaper"></i> Tin Bài</a></li>
                <li><a href="social.php"><i class="fas fa-share-alt"></i> Social Links</a></li>
                <li><a href="server_info.php"><i class="fas fa-server"></i> Thông Tin Server</a></li>
                <li><a href="weekly_events.php"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Quản Lý User</a></li>
                <li><a href="lucky_wheel.php" class="active"><i class="fas fa-dharmachakra"></i> Vòng Quay May Mắn</a></li>
                <li><a href="tichnap/index.php"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-dharmachakra"></i> Quản Lý Vòng Quay May Mắn</h2>
                <p>Bật/tắt tính năng, thêm/sửa/xóa vật phẩm trong vòng quay</p>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertMessage" class="alert" style="display: none;"></div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('config')">
                    <i class="fas fa-cog"></i> Cấu Hình
                </button>
                <button class="tab" onclick="switchTab('seasons')">
                    <i class="fas fa-calendar-alt"></i> Quản Lý Mùa
                </button>
                <button class="tab" onclick="switchTab('wheel-items')">
                    <i class="fas fa-list"></i> Vật Phẩm Vòng Quay
                </button>
                <button class="tab" onclick="switchTab('accumulated-items')">
                    <i class="fas fa-gift"></i> Vật Phẩm Mốc Quay
                </button>
                <button class="tab" onclick="switchTab('test-items')">
                    <i class="fas fa-flask"></i> Test Vật Phẩm
                </button>
                <button class="tab" onclick="switchTab('guide')">
                    <i class="fas fa-book"></i> Hướng Dẫn
                </button>
            </div>
            
            <!-- Tab: Cấu Hình -->
            <div id="configTab" class="tab-content active">
                <!-- Configuration Section -->
                <div class="config-section">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                        <i class="fas fa-cog"></i> Cấu Hình
                    </h3>
                    
                    <div class="config-row">
                        <div>
                            <div class="config-label">Trạng thái tính năng</div>
                            <div class="config-value" style="font-size: 0.9rem; margin-top: 5px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="featureToggle" onchange="toggleFeature()">
                                    <span class="toggle-slider"></span>
                                </label>
                                <span id="featureStatus" style="margin-left: 10px;">Đang tải...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <div>
                            <div class="config-label">Giá quay (Silk/lần)</div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                                <input type="number" id="spinCost" min="1" max="1000" class="form-control" style="width: 150px;">
                                <button class="btn btn-primary btn-sm" onclick="updateSpinCost()">
                                    <i class="fas fa-save"></i> Lưu
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Vật Phẩm Vòng Quay -->
            <div id="wheelItemsTab" class="tab-content">
                <div class="form-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: #e8c088; margin: 0;">
                            <i class="fas fa-list"></i> Danh Sách Vật Phẩm Vòng Quay
                        </h3>
                        <button class="btn btn-primary" onclick="openAddItemModal()">
                            <i class="fas fa-plus"></i> Thêm Vật Phẩm
                        </button>
                    </div>
                
                    <!-- Loading -->
                    <div id="loading" class="loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                    
                    <!-- Items Table -->
                    <div class="table-container">
                        <table id="itemsTable">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên vật phẩm</th>
                                    <th>Mã vật phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Vật phẩm hiếm</th>
                                    <th>Tỉ lệ quay ra (%)</th>
                                    <th>Thứ tự</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px; color: #87ceeb;">
                                        Đang tải...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Test Vật Phẩm -->
            <div id="testItemsTab" class="tab-content">
                <div class="form-container">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                        <i class="fas fa-flask"></i> Test Vật Phẩm Vòng Quay
                    </h3>
                    
                    <div class="config-section" style="margin-bottom: 20px;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Mô Phỏng Quay Vòng
                        </h4>
                        <p style="color: #87ceeb; line-height: 1.8; margin-bottom: 15px;">
                            Nhập số lượng vòng quay để mô phỏng và xem thống kê kết quả. 
                            Hệ thống sẽ mô phỏng quay nhiều lần và hiển thị tỉ lệ thực tế so với tỉ lệ cấu hình.
                        </p>
                        
                        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <label for="testSpinCount" style="color: #e8c088; font-weight: 600; display: block; margin-bottom: 8px;">
                                    Số lượng vòng quay
                                </label>
                                <input 
                                    type="number" 
                                    id="testSpinCount" 
                                    min="1" 
                                    max="10000" 
                                    value="1000" 
                                    class="form-control"
                                    style="width: 100%;"
                                    placeholder="Nhập số lần quay (1-10000)"
                                >
                                <small style="color: #87ceeb; display: block; margin-top: 5px;">
                                    Số lần quay để mô phỏng (khuyến nghị: 1000-10000 để có kết quả chính xác)
                                </small>
                            </div>
                            <div style="margin-top: 25px;">
                                <button class="btn btn-primary" onclick="runTestSpin()" id="testSpinBtn">
                                    <i class="fas fa-play"></i> Chạy Test
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading -->
                    <div id="loadingTest" class="loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Đang mô phỏng quay...
                    </div>
                    
                    <!-- Test Results -->
                    <div id="testResults" style="display: none;">
                        <div class="config-section" style="margin-bottom: 20px;">
                            <h4 style="color: #e8c088; margin-bottom: 15px;">
                                <i class="fas fa-chart-bar"></i> Thống Kê Kết Quả
                            </h4>
                            <div id="testSummary" style="color: #87ceeb; line-height: 1.8;"></div>
                        </div>
                        
                        <!-- Statistics Table -->
                        <div class="table-container">
                            <table id="testStatisticsTable">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên vật phẩm</th>
                                        <th>Mã vật phẩm</th>
                                        <th>Số lượng</th>
                                        <th>Vật phẩm hiếm</th>
                                        <th>Tỉ lệ cấu hình (%)</th>
                                        <th>Số lần quay ra</th>
                                        <th>Tỉ lệ thực tế (%)</th>
                                        <th>Số lần dự kiến</th>
                                        <th>Chênh lệch</th>
                                    </tr>
                                </thead>
                                <tbody id="testStatisticsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Hướng Dẫn -->
            <!-- Tab: Quản Lý Mùa -->
            <div id="seasonsTab" class="tab-content">
                <div class="config-section">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                        <i class="fas fa-calendar-alt"></i> Quản Lý Mùa Giải
                    </h3>
                    
                    <!-- Danh Sách Mùa (table + modal giống UI vật phẩm) -->
                    <div class="card" style="background: rgba(30, 35, 60, 0.8); border: 1px solid #444; border-radius: 8px; padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h4 style="color: #87ceeb; margin: 0;">
                                <i class="fas fa-list"></i> Danh Sách Mùa
                            </h4>
                            <button class="btn btn-primary" onclick="openSeasonModal()">
                                <i class="fas fa-plus"></i> Tạo Mùa
                            </button>
                        </div>
                        <div class="table-container">
                            <table id="seasonsTable">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên mùa</th>
                                        <th>Thời gian</th>
                                        <th>Trạng thái</th>
                                        <th>Tổng lượt quay</th>
                                        <th>Tổng người</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="seasonsTableBody">
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 20px; color: #87ceeb;">
                                            <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="guideTab" class="tab-content">
                <div class="form-container">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                        <i class="fas fa-book"></i> Hướng Dẫn Sử Dụng Vòng Quay May Mắn
                    </h3>
                    
                    <div class="config-section" style="margin-bottom: 20px;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Tổng Quan
                        </h4>
                        <p style="color: #87ceeb; line-height: 1.8;">
                            Hệ thống Vòng Quay May Mắn cho phép người chơi quay vòng để nhận phần thưởng. 
                            Mỗi lần quay tốn một số lượng Silk nhất định (mặc định: 10 Silk/lần).
                        </p>
                    </div>
                    
                    <div class="config-section" style="margin-bottom: 20px;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-cog"></i> Cấu Hình
                        </h4>
                        <ul style="color: #87ceeb; line-height: 2; padding-left: 20px;">
                            <li><strong>Bật/Tắt tính năng:</strong> Cho phép bật hoặc tắt tính năng vòng quay cho toàn bộ người chơi.</li>
                            <li><strong>Giá quay:</strong> Số lượng Silk cần thiết cho mỗi lần quay (tối thiểu: 1, tối đa: 1000).</li>
                        </ul>
                    </div>
                    
                    <div class="config-section" style="margin-bottom: 20px;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-list"></i> Vật Phẩm Vòng Quay
                        </h4>
                        <ul style="color: #87ceeb; line-height: 2; padding-left: 20px;">
                            <li><strong>Thêm vật phẩm:</strong> Nhấn nút "Thêm Vật Phẩm" và điền đầy đủ thông tin:
                                <ul style="margin-top: 10px; padding-left: 20px;">
                                    <li>Tên vật phẩm (tối đa 100 ký tự)</li>
                                    <li>Mã vật phẩm (tối đa 50 ký tự, phải là mã code trong game)</li>
                                    <li>Số lượng vật phẩm (phải > 0)</li>
                                    <li>Vật phẩm hiếm: Đánh dấu nếu muốn hiển thị trên ticker trang chủ</li>
                                    <li>Tỉ lệ quay ra (%): Từ 0.01 đến 100</li>
                                </ul>
                            </li>
                            <li><strong>Thứ tự hiển thị:</strong> Được tự động quản lý bởi hệ thống (tự động tăng dần).</li>
                            <li><strong>Xóa vật phẩm:</strong> Sử dụng soft delete (IsActive = 0), không hiển thị ở admin và user nhưng vẫn giữ data để user có thể claim phần thưởng đã trúng.</li>
                        </ul>
                    </div>
                    
                    <div class="config-section" style="margin-bottom: 20px;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-gift"></i> Vật Phẩm Mốc Quay (Tích Lũy)
                        </h4>
                        <ul style="color: #87ceeb; line-height: 2; padding-left: 20px;">
                            <li><strong>Thêm vật phẩm mốc quay:</strong> Nhấn nút "Thêm Vật Phẩm Mốc Quay" và điền:
                                <ul style="margin-top: 10px; padding-left: 20px;">
                                    <li>Tên vật phẩm (tối đa 100 ký tự)</li>
                                    <li>Mã vật phẩm (tối đa 50 ký tự)</li>
                                    <li>Số lượng vật phẩm (phải > 0)</li>
                                    <li>Mức đạt (số vòng): Số vòng quay cần đạt để nhận phần thưởng này</li>
                                </ul>
                            </li>
                            <li><strong>Cơ chế tích lũy:</strong> Hệ thống tự động đếm tổng số vòng quay của mỗi user. Khi đạt mức yêu cầu, user có thể nhận phần thưởng.</li>
                            <li><strong>Chỉ nhận 1 lần:</strong> Mỗi phần thưởng tích lũy chỉ có thể nhận được 1 lần duy nhất (có unique constraint ở database).</li>
                        </ul>
                    </div>
                    
                    <div class="config-section" style="margin-bottom: 20px;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-shield-alt"></i> Workflow Nhận Thưởng - Đảm Bảo Chỉ Nhận 1 Lần
                        </h4>
                        
                        <h5 style="color: #e8c088; margin-top: 15px; margin-bottom: 10px;">
                            <i class="fas fa-dharmachakra"></i> Nhận Thưởng Từ Quay Vòng
                        </h5>
                        <ol style="color: #87ceeb; line-height: 2; padding-left: 20px;">
                            <li><strong>Kiểm tra trạng thái:</strong> Chỉ cho phép claim nếu Status = 'pending'</li>
                            <li><strong>Transaction:</strong> Sử dụng database transaction để đảm bảo atomicity</li>
                            <li><strong>Double-check trong transaction:</strong> Kiểm tra lại Status = 'pending' trước khi xử lý</li>
                            <li><strong>Giao vật phẩm:</strong> Thêm vật phẩm vào game qua _InstantItemDelivery</li>
                            <li><strong>Cập nhật trạng thái:</strong> UPDATE Status = 'claimed' với điều kiện WHERE Status = 'pending' (ngăn double claim)</li>
                            <li><strong>Xác minh:</strong> Kiểm tra rowCount() để đảm bảo update thành công</li>
                        </ol>
                        
                        <h5 style="color: #e8c088; margin-top: 15px; margin-bottom: 10px;">
                            <i class="fas fa-trophy"></i> Nhận Thưởng Tích Lũy
                        </h5>
                        <ol style="color: #87ceeb; line-height: 2; padding-left: 20px;">
                            <li><strong>Kiểm tra đã claim:</strong> Gọi hasClaimedAccumulatedReward() để kiểm tra</li>
                            <li><strong>Kiểm tra mức đạt:</strong> Tổng số vòng quay >= RequiredSpins</li>
                            <li><strong>Transaction:</strong> Sử dụng database transaction</li>
                            <li><strong>Double-check trong transaction:</strong> Kiểm tra lại claim status trước khi xử lý</li>
                            <li><strong>Giao vật phẩm:</strong> Thêm vật phẩm vào game</li>
                            <li><strong>Ghi log:</strong> INSERT vào LuckyWheelAccumulatedLog với unique constraint (UserJID, AccumulatedItemId)</li>
                            <li><strong>Xử lý lỗi unique:</strong> Nếu có lỗi unique constraint, báo "đã nhận rồi"</li>
                        </ol>
                        
                        <div style="background: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107; padding: 15px; margin-top: 15px; border-radius: 5px;">
                            <p style="color: #ffc107; margin: 0; font-weight: bold;">
                                <i class="fas fa-exclamation-triangle"></i> Lưu Ý Bảo Mật:
                            </p>
                            <ul style="color: #87ceeb; line-height: 1.8; margin-top: 10px; padding-left: 20px;">
                                <li>Mỗi reward ID chỉ có thể claim 1 lần (Status: pending → claimed)</li>
                                <li>Mỗi accumulated item chỉ có thể claim 1 lần/user (unique constraint)</li>
                                <li>Transaction đảm bảo không có race condition</li>
                                <li>Double-check trong transaction ngăn chặn double claim</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="config-section" style="margin-bottom: 20px;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-database"></i> Cấu Trúc Database
                        </h4>
                        <ul style="color: #87ceeb; line-height: 2; padding-left: 20px;">
                            <li><strong>LuckyWheelConfig:</strong> Cấu hình tính năng (bật/tắt, giá quay)</li>
                            <li><strong>LuckyWheelItems:</strong> Danh sách vật phẩm trong vòng quay</li>
                            <li><strong>LuckyWheelLog:</strong> Log mỗi lần quay (lưu lịch sử)</li>
                            <li><strong>LuckyWheelRewards:</strong> Phần thưởng đã trúng, chờ nhận (Status: pending/claimed)</li>
                            <li><strong>LuckyWheelAccumulatedItems:</strong> Danh sách phần thưởng tích lũy</li>
                            <li><strong>LuckyWheelAccumulatedLog:</strong> Log nhận phần thưởng tích lũy (unique: UserJID + AccumulatedItemId)</li>
                            <li><strong>TB_User.TotalSpins:</strong> Tổng số vòng quay của mỗi user</li>
                        </ul>
                    </div>
                    
                    <div class="config-section">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-question-circle"></i> Câu Hỏi Thường Gặp
                        </h4>
                        <div style="color: #87ceeb; line-height: 2;">
                            <p><strong>Q: Làm sao đảm bảo user không claim 2 lần?</strong></p>
                            <p style="margin-left: 20px; margin-bottom: 15px;">
                                A: Hệ thống sử dụng transaction + double-check + unique constraint. 
                                Mỗi reward ID chỉ có thể claim 1 lần (Status: pending → claimed). 
                                Mỗi accumulated item chỉ có thể claim 1 lần/user (unique constraint ở database).
                            </p>
                            
                            <p><strong>Q: Xóa vật phẩm có ảnh hưởng đến phần thưởng đã trúng không?</strong></p>
                            <p style="margin-left: 20px; margin-bottom: 15px;">
                                A: Không. Hệ thống sử dụng soft delete (IsActive = 0). 
                                Vật phẩm không hiển thị ở admin và user, nhưng data vẫn được giữ để user có thể claim phần thưởng đã trúng.
                            </p>
                            
                            <p><strong>Q: Làm sao tính tỉ lệ quay ra?</strong></p>
                            <p style="margin-left: 20px;">
                                A: Hệ thống sử dụng Weighted Random Algorithm. 
                                Tổng tỉ lệ = tổng WinRate của tất cả vật phẩm active. 
                                Mỗi vật phẩm có xác suất = WinRate / Tổng tỉ lệ.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Vật Phẩm Mốc Quay -->
            <div id="accumulatedItemsTab" class="tab-content">
                <div class="form-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: #e8c088; margin: 0;">
                            <i class="fas fa-gift"></i> Vật Phẩm Mốc Quay (Quay Tích Lũy)
                        </h3>
                        <button class="btn btn-primary" onclick="openAddAccumulatedItemModal()">
                            <i class="fas fa-plus"></i> Thêm Vật Phẩm Mốc Quay
                        </button>
                    </div>
                    
                    <!-- Loading -->
                    <div id="loadingAccumulated" class="loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                    
                    <!-- Accumulated Items Table -->
                    <div class="table-container">
                        <table id="accumulatedItemsTable">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên vật phẩm</th>
                                    <th>Mã vật phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Mức đạt (vòng)</th>
                                    <th>Thứ tự</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="accumulatedItemsTableBody">
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 20px; color: #87ceeb;">
                                        Đang tải...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal: Season create/edit -->
    <div id="seasonModal" class="modal">
        <div class="modal-content" style="max-width: 640px;">
            <div class="modal-header">
                <h3 id="seasonModalTitle"><i class="fas fa-plus-circle"></i> Tạo Mùa</h3>
                <span class="close" onclick="closeModal('seasonModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createSeasonForm">
                    <div class="form-group">
                        <label style="color: #e8c088;">Tên Mùa *</label>
                        <input type="text" id="seasonName" name="season_name" required 
                               style="width: 100%; padding: 8px; background: rgba(20, 25, 40, 0.8); border: 1px solid #555; border-radius: 4px; color: #fff;"
                               placeholder="Ví dụ: Mùa 1">
                    </div>
                    <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="color: #e8c088;">Ngày Bắt Đầu *</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="datetime-local" id="startDate" name="start_date" required
                                       style="width: 100%; padding: 8px; background: rgba(20, 25, 40, 0.8); border: 1px solid #555; border-radius: 4px; color: #fff;">
                                <button type="button" class="btn btn-secondary" style="padding: 8px 10px;" onclick="openDatePicker('startDate')">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label style="color: #e8c088;">Ngày Kết Thúc</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="datetime-local" id="endDate" name="end_date"
                                       style="width: 100%; padding: 8px; background: rgba(20, 25, 40, 0.8); border: 1px solid #555; border-radius: 4px; color: #fff;">
                                <button type="button" class="btn btn-secondary" style="padding: 8px 10px;" onclick="openDatePicker('endDate')">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            </div>
                            <small style="color: #87ceeb; font-size: 0.85rem;">(Để trống để tự động tính)</small>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 16px;">
                            <i class="fas fa-save"></i> Lưu
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('seasonModal')" style="padding: 10px 16px;">
                            Đóng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal: Add/Edit Accumulated Item -->
    <div id="accumulatedItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="accumulatedModalTitle"><i class="fas fa-plus"></i> Thêm Vật Phẩm Mốc Quay</h3>
                <span class="close" onclick="closeModal('accumulatedItemModal')">&times;</span>
            </div>
            <form id="accumulatedItemForm" onsubmit="saveAccumulatedItem(event)">
                <div class="form-group">
                    <label for="accumulatedItemName">Tên vật phẩm *</label>
                    <input type="text" id="accumulatedItemName" required maxlength="100" placeholder="Nhập tên vật phẩm (tối đa 100 ký tự)">
                </div>
                
                <div class="form-group">
                    <label for="accumulatedItemCode">Mã vật phẩm *</label>
                    <input type="text" id="accumulatedItemCode" required maxlength="50" placeholder="Nhập mã vật phẩm (tối đa 50 ký tự)">
                    <small>Mã code của vật phẩm trong game</small>
                </div>
                
                <div class="form-group">
                    <label for="accumulatedQuantity">Số lượng *</label>
                    <input type="number" id="accumulatedQuantity" required min="1" value="1">
                    <small>Số lượng vật phẩm nhận được khi đạt mức</small>
                </div>
                
                <div class="form-group">
                    <label for="requiredSpins">Mức đạt (số vòng) *</label>
                    <input type="number" id="requiredSpins" required min="1" placeholder="Ví dụ: 10, 50, 100">
                    <small>Số vòng quay cần đạt để nhận phần thưởng này</small>
                </div>
                
                <input type="hidden" id="accumulatedItemId">
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('accumulatedItemModal')">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Add/Edit Item -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-plus"></i> Thêm Vật Phẩm</h3>
                <span class="close" onclick="closeModal('itemModal')">&times;</span>
            </div>
            <form id="itemForm" onsubmit="saveItem(event)">
                <div class="form-group">
                    <label for="itemName">Tên vật phẩm *</label>
                    <input type="text" id="itemName" required maxlength="100" placeholder="Nhập tên vật phẩm (tối đa 100 ký tự)">
                </div>
                
                <div class="form-group">
                    <label for="itemCode">Mã vật phẩm *</label>
                    <input type="text" id="itemCode" required maxlength="50" placeholder="Nhập mã vật phẩm (tối đa 50 ký tự)">
                    <small>Mã code của vật phẩm trong game</small>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Số lượng *</label>
                    <input type="number" id="quantity" required min="1" value="1">
                    <small>Số lượng vật phẩm nhận được khi trúng</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="isRare" style="width: auto; margin-right: 8px; cursor: pointer;">
                        Vật phẩm hiếm
                    </label>
                    <small>Đánh dấu vật phẩm hiếm sẽ hiển thị trên ticker trang chủ</small>
                </div>
                
                <div class="form-group">
                    <label for="winRate">Tỉ lệ quay ra (%) *</label>
                    <input type="number" id="winRate" required min="0.01" max="100" step="0.01" placeholder="0.00">
                    <small>Tỉ lệ phần trăm để quay ra vật phẩm này (0.01 - 100)</small>
                </div>
                
                <!-- Display order is automatically managed by the system -->
                <input type="hidden" id="displayOrder" value="0">
                
                <input type="hidden" id="itemId">
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('itemModal')">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Switch tabs
        function switchTab(tabName) {
            // Load seasons when switching to seasons tab
            if (tabName === 'seasons') {
                loadSeasons();
            }
            // Remove active class from all tabs and contents
            $('.tab').removeClass('active');
            $('.tab-content').removeClass('active');
            
            if (tabName === 'config') {
                $('.tab').eq(0).addClass('active');
                $('#configTab').addClass('active');
                loadConfig();
            } else if (tabName === 'wheel-items') {
                $('.tab').eq(1).addClass('active');
                $('#wheelItemsTab').addClass('active');
                loadItems();
            } else if (tabName === 'accumulated-items') {
                $('.tab').eq(2).addClass('active');
                $('#accumulatedItemsTab').addClass('active');
                loadAccumulatedItems();
            } else if (tabName === 'test-items') {
                $('.tab').eq(3).addClass('active');
                $('#testItemsTab').addClass('active');
                // Test tab doesn't need to load data on switch
            } else if (tabName === 'seasons') {
                $('.tab').eq(4).addClass('active');
                $('#seasonsTab').addClass('active');
                loadSeasons();
            } else if (tabName === 'guide') {
                $('.tab').eq(5).addClass('active');
                $('#guideTab').addClass('active');
                // Guide tab doesn't need to load data
            }
        }
        
        // Load config and items on page load
        $(document).ready(function() {
            // Only load data for active tab on page load
            loadConfig();
        });
        
        // Load configuration
        function loadConfig() {
            $.ajax({
                url: '/api/cms/lucky_wheel/get_config.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#featureToggle').prop('checked', response.data.FeatureEnabled);
                        $('#featureStatus').text(response.data.FeatureEnabled ? 'Đã bật' : 'Đã tắt');
                        $('#spinCost').val(response.data.SpinCost);
                    }
                },
                error: function() {
                    showAlert('error', 'Không thể tải cấu hình');
                }
            });
        }
        
        // Toggle feature
        function toggleFeature() {
            const enabled = $('#featureToggle').is(':checked');
            
            $.ajax({
                url: '/api/cms/lucky_wheel/toggle_feature.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ enabled: enabled }),
                success: function(response) {
                    if (response.success) {
                        $('#featureStatus').text(enabled ? 'Đã bật' : 'Đã tắt');
                        showAlert('success', response.message);
                    } else {
                        $('#featureToggle').prop('checked', !enabled);
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    $('#featureToggle').prop('checked', !enabled);
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Có lỗi xảy ra');
                }
            });
        }
        
        // Update spin cost
        function updateSpinCost() {
            const spinCost = parseInt($('#spinCost').val());
            
            if (spinCost <= 0) {
                showAlert('error', 'Giá quay phải lớn hơn 0');
                return;
            }
            
            $.ajax({
                url: '/api/cms/lucky_wheel/toggle_feature.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ 
                    enabled: $('#featureToggle').is(':checked'),
                    spin_cost: spinCost 
                }),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Đã cập nhật giá quay thành công');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Có lỗi xảy ra');
                }
            });
        }
        
        // Load items
        function loadItems() {
            $('#loading').show();
            $('#itemsTableBody').html('<tr><td colspan="9" style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
            
            // Only load active items (not include inactive/deleted items)
            $.ajax({
                url: '/api/cms/lucky_wheel/get_items.php?include_inactive=0',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#loading').hide();
                    
                    if (response.success) {
                        displayItems(response.data);
                    } else {
                        showAlert('error', response.error || 'Có lỗi xảy ra');
                    }
                },
                error: function(xhr) {
                    $('#loading').hide();
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Lỗi kết nối');
                }
            });
        }
        
        // Display items in table
        function displayItems(items) {
            const tbody = $('#itemsTableBody');
            tbody.empty();
            
            if (items.length === 0) {
                tbody.html('<tr><td colspan="9" style="text-align: center; padding: 20px; color: #87ceeb;">Chưa có vật phẩm nào</td></tr>');
                return;
            }
            
            items.forEach(function(item, index) {
                // Convert IsRare to boolean (handle both 0/1 and true/false)
                const isRare = item.IsRare === true || item.IsRare === 1 || item.IsRare === '1';
                const rareClass = isRare ? 'rare-yes' : 'rare-no';
                const rareText = isRare ? 'Có' : 'Không';
                const activeText = item.IsActive ? 'Hoạt động' : 'Vô hiệu';
                const activeClass = item.IsActive ? 'success' : 'error';
                
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(item.ItemName)}</td>
                        <td><code style="color: #87ceeb;">${escapeHtml(item.ItemCode)}</code></td>
                        <td>${item.Quantity}</td>
                        <td><span class="rare-badge ${rareClass}">${rareText}</span></td>
                        <td><span class="win-rate-badge">${parseFloat(item.WinRate).toFixed(2)}%</span></td>
                        <td>${item.DisplayOrder}</td>
                        <td><span class="alert alert-${activeClass}" style="padding: 4px 12px; display: inline-block; margin: 0;">${activeText}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="openEditItemModal(${item.Id})">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteItem(${item.Id}, '${escapeHtml(item.ItemName)}')">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        
        // Open add item modal
        function openAddItemModal() {
            $('#modalTitle').html('<i class="fas fa-plus"></i> Thêm Vật Phẩm');
            $('#itemForm')[0].reset();
            $('#itemId').val('');
            $('#quantity').val(1);
            // Display order is automatically managed by backend
            $('#displayOrder').val(0);
            // Ensure checkbox is unchecked and enabled
            $('#isRare').prop('checked', false).prop('disabled', false);
            $('#itemModal').show();
        }
        
        // Open edit item modal
        function openEditItemModal(itemId) {
            // Load with inactive to allow editing items that might be inactive
            // But in practice, we only show active items, so this should only be called for active items
            $.ajax({
                url: '/api/cms/lucky_wheel/get_items.php?include_inactive=1',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const item = response.data.find(i => i.Id == itemId);
                        if (item) {
                            $('#modalTitle').html('<i class="fas fa-edit"></i> Sửa Vật Phẩm');
                            $('#itemId').val(item.Id);
                            $('#itemName').val(item.ItemName);
                            $('#itemCode').val(item.ItemCode);
                            $('#quantity').val(item.Quantity);
                            // Convert IsRare to boolean (handle both 0/1 and true/false)
                            const isRare = item.IsRare === true || item.IsRare === 1 || item.IsRare === '1';
                            $('#isRare').prop('checked', isRare).prop('disabled', false);
                            $('#winRate').val(item.WinRate);
                            // Display order is read-only (automatically managed by backend)
                            $('#displayOrder').val(item.DisplayOrder);
                            $('#itemModal').show();
                        }
                    }
                }
            });
        }
        
        // Save item
        function saveItem(event) {
            event.preventDefault();
            
            const itemId = $('#itemId').val();
            const data = {
                item_name: $('#itemName').val().trim(),
                item_code: $('#itemCode').val().trim(),
                quantity: parseInt($('#quantity').val()),
                is_rare: $('#isRare').is(':checked'),
                win_rate: parseFloat($('#winRate').val())
                // display_order is automatically managed by backend
            };
            
            const url = itemId ? '/api/cms/lucky_wheel/update_item.php' : '/api/cms/lucky_wheel/add_item.php';
            if (itemId) {
                data.id = parseInt(itemId);
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        closeModal('itemModal');
                        loadItems();
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Có lỗi xảy ra');
                }
            });
        }
        
        // Delete item
        function deleteItem(itemId, itemName) {
            if (!confirm(`Xác nhận xóa vật phẩm "${itemName}"?`)) {
                return;
            }
            
            $.ajax({
                url: '/api/cms/lucky_wheel/delete_item.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: itemId }),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        loadItems();
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Có lỗi xảy ra');
                }
            });
        }
        
        // Close modal
        function closeModal(modalId) {
            $('#' + modalId).hide();
        }
        
        // Show alert
        function showAlert(type, message) {
            const alertDiv = $('#alertMessage');
            alertDiv.removeClass('alert-success alert-error');
            alertDiv.addClass('alert-' + type);
            alertDiv.html('<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + escapeHtml(message));
            alertDiv.show();
            
            setTimeout(function() {
                alertDiv.fadeOut();
            }, 5000);
        }
        
        // Utility functions
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
        
        // ========== Accumulated Items Management ==========
        
        // Load accumulated items
        function loadAccumulatedItems() {
            const tbody = $('#accumulatedItemsTableBody');
            if (tbody.length === 0) {
                console.error('accumulatedItemsTableBody not found');
                return;
            }
            
            $('#loadingAccumulated').show();
            tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
            
            // Only load active items (not include inactive/deleted items)
            $.ajax({
                url: '/api/cms/lucky_wheel/get_accumulated_items.php?include_inactive=0',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    $('#loadingAccumulated').hide();
                    
                    if (response.success) {
                        displayAccumulatedItems(response.data);
                    } else {
                        tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px; color: #ff6b6b;">' + (response.error || 'Có lỗi xảy ra') + '</td></tr>');
                        showAlert('error', response.error || 'Có lỗi xảy ra');
                    }
                },
                error: function(xhr, status, error) {
                    $('#loadingAccumulated').hide();
                    const response = xhr.responseJSON;
                    const errorMsg = response?.error || 'Lỗi kết nối: ' + error + ' (Status: ' + xhr.status + ')';
                    tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px; color: #ff6b6b;">' + errorMsg + '</td></tr>');
                    showAlert('error', errorMsg);
                }
            });
        }
        
        // Display accumulated items in table
        function displayAccumulatedItems(items) {
            const tbody = $('#accumulatedItemsTableBody');
            if (tbody.length === 0) {
                console.error('accumulatedItemsTableBody not found');
                return;
            }
            
            tbody.empty();
            
            if (!items || items.length === 0) {
                tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px; color: #87ceeb;">Chưa có vật phẩm mốc quay nào</td></tr>');
                return;
            }
            
            items.forEach(function(item, index) {
                const activeText = item.IsActive ? 'Hoạt động' : 'Vô hiệu';
                const activeClass = item.IsActive ? 'success' : 'error';
                
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(item.ItemName)}</td>
                        <td><code style="color: #87ceeb;">${escapeHtml(item.ItemCode)}</code></td>
                        <td>${item.Quantity}</td>
                        <td><span class="win-rate-badge">${item.RequiredSpins} vòng</span></td>
                        <td>${item.DisplayOrder}</td>
                        <td><span class="alert alert-${activeClass}" style="padding: 4px 12px; display: inline-block; margin: 0;">${activeText}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="openEditAccumulatedItemModal(${item.Id})">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteAccumulatedItem(${item.Id}, '${escapeHtml(item.ItemName)}')">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        
        // Open add accumulated item modal
        function openAddAccumulatedItemModal() {
            $('#accumulatedModalTitle').html('<i class="fas fa-plus"></i> Thêm Vật Phẩm Mốc Quay');
            $('#accumulatedItemForm')[0].reset();
            $('#accumulatedItemId').val('');
            $('#accumulatedQuantity').val(1);
            $('#accumulatedItemModal').show();
        }
        
        // Open edit accumulated item modal
        function openEditAccumulatedItemModal(itemId) {
            $.ajax({
                url: '/api/cms/lucky_wheel/get_accumulated_items.php?include_inactive=1',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const item = response.data.find(i => i.Id == itemId);
                        if (item) {
                            $('#accumulatedModalTitle').html('<i class="fas fa-edit"></i> Sửa Vật Phẩm Mốc Quay');
                            $('#accumulatedItemId').val(item.Id);
                            $('#accumulatedItemName').val(item.ItemName);
                            $('#accumulatedItemCode').val(item.ItemCode);
                            $('#accumulatedQuantity').val(item.Quantity);
                            $('#requiredSpins').val(item.RequiredSpins);
                            $('#accumulatedItemModal').show();
                        }
                    }
                }
            });
        }
        
        // Save accumulated item
        function saveAccumulatedItem(event) {
            event.preventDefault();
            
            const itemId = $('#accumulatedItemId').val();
            const data = {
                item_name: $('#accumulatedItemName').val().trim(),
                item_code: $('#accumulatedItemCode').val().trim(),
                quantity: parseInt($('#accumulatedQuantity').val()),
                required_spins: parseInt($('#requiredSpins').val())
            };
            
            const url = itemId ? '/api/cms/lucky_wheel/update_accumulated_item.php' : '/api/cms/lucky_wheel/add_accumulated_item.php';
            if (itemId) {
                data.id = parseInt(itemId);
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        closeModal('accumulatedItemModal');
                        loadAccumulatedItems();
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Có lỗi xảy ra');
                }
            });
        }
        
        // Delete accumulated item
        function deleteAccumulatedItem(itemId, itemName) {
            if (!confirm(`Xác nhận xóa vật phẩm mốc quay "${itemName}"?`)) {
                return;
            }
            
            $.ajax({
                url: '/api/cms/lucky_wheel/delete_accumulated_item.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: itemId }),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        loadAccumulatedItems();
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Có lỗi xảy ra');
                }
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // ========== Test Spin Functions ==========
        
        // Run test spin
        function runTestSpin() {
            const spinCount = parseInt($('#testSpinCount').val());
            
            if (!spinCount || spinCount <= 0 || spinCount > 10000) {
                showAlert('error', 'Số lần quay phải từ 1 đến 10000');
                return;
            }
            
            $('#loadingTest').show();
            $('#testResults').hide();
            $('#testSpinBtn').prop('disabled', true);
            
            $.ajax({
                url: '/api/cms/lucky_wheel/test_spin.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ spin_count: spinCount }),
                success: function(response) {
                    $('#loadingTest').hide();
                    $('#testSpinBtn').prop('disabled', false);
                    
                    if (response.success) {
                        displayTestResults(response.data);
                    } else {
                        showAlert('error', response.error || 'Có lỗi xảy ra');
                    }
                },
                error: function(xhr) {
                    $('#loadingTest').hide();
                    $('#testSpinBtn').prop('disabled', false);
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Lỗi kết nối');
                }
            });
        }
        
        // Display test results
        function displayTestResults(data) {
            const tbody = $('#testStatisticsTableBody');
            tbody.empty();
            
            // Display summary
            const summary = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong style="color: #e8c088;">Tổng số lần quay:</strong>
                        <span style="color: #87ceeb; margin-left: 10px;">${data.spin_count.toLocaleString()}</span>
                    </div>
                    <div>
                        <strong style="color: #e8c088;">Tổng tỉ lệ:</strong>
                        <span style="color: #87ceeb; margin-left: 10px;">${parseFloat(data.total_rate).toFixed(2)}%</span>
                    </div>
                    <div>
                        <strong style="color: #e8c088;">Số vật phẩm:</strong>
                        <span style="color: #87ceeb; margin-left: 10px;">${data.items_count}</span>
                    </div>
                </div>
            `;
            $('#testSummary').html(summary);
            
            // Display statistics table
            if (data.statistics && data.statistics.length > 0) {
                data.statistics.forEach(function(stat, index) {
                    const difference = stat.actual_count - stat.expected_count;
                    const differencePercent = stat.actual_percentage - stat.win_rate;
                    const diffClass = Math.abs(differencePercent) < 1 ? 'success' : (differencePercent > 0 ? 'warning' : 'error');
                    const rareClass = stat.is_rare ? 'rare-yes' : 'rare-no';
                    const rareText = stat.is_rare ? 'Có' : 'Không';
                    
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${escapeHtml(stat.item_name)}</td>
                            <td><code style="color: #87ceeb;">${escapeHtml(stat.item_code)}</code></td>
                            <td>${stat.quantity}</td>
                            <td><span class="rare-badge ${rareClass}">${rareText}</span></td>
                            <td><span class="win-rate-badge">${parseFloat(stat.win_rate).toFixed(2)}%</span></td>
                            <td><strong style="color: #87ceeb;">${stat.actual_count.toLocaleString()}</strong></td>
                            <td><span class="win-rate-badge">${parseFloat(stat.actual_percentage).toFixed(2)}%</span></td>
                            <td>${parseFloat(stat.expected_count).toFixed(2)}</td>
                            <td>
                                <span class="alert alert-${diffClass}" style="padding: 4px 8px; display: inline-block; margin: 0; font-size: 0.9rem;">
                                    ${difference >= 0 ? '+' : ''}${difference.toFixed(2)} 
                                    (${differencePercent >= 0 ? '+' : ''}${differencePercent.toFixed(2)}%)
                                </span>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                tbody.html('<tr><td colspan="10" style="text-align: center; padding: 20px; color: #87ceeb;">Không có dữ liệu</td></tr>');
            }
            
            $('#testResults').show();
        }
        
        // ==========================================
        // Season Management Functions
        // ==========================================
        
        // Load seasons list
        function loadSeasons() {
            $('#seasonsTableBody').html('<tr><td colspan="7" style="text-align: center; padding: 20px; color: #87ceeb;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
            
            $.ajax({
                url: '/api/cms/lucky_wheel/get_seasons.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displaySeasons(response.data);
                    } else {
                        $('#seasonsTableBody').html('<tr><td colspan="7" style="text-align: center; padding: 20px; color: #ff6b6b;">Lỗi: ' + (response.error || 'Không thể tải danh sách mùa') + '</td></tr>');
                    }
                },
                error: function() {
                    $('#seasonsTableBody').html('<tr><td colspan="7" style="text-align: center; padding: 20px; color: #ff6b6b;">Lỗi kết nối</td></tr>');
                }
            });
        }
        
        // Display seasons list
        function displaySeasons(seasons) {
            const featureEnabled = $('#featureToggle').is(':checked');
            if (!seasons || seasons.length === 0) {
                $('#seasonsTableBody').html('<tr><td colspan="7" style="text-align: center; padding: 20px; color: #87ceeb;">Chưa có mùa nào</td></tr>');
                return;
            }
            
            const tbody = $('#seasonsTableBody');
            tbody.empty();
            
            seasons.forEach(function(season, idx) {
                const statusClass = season.Status === 'ACTIVE' ? 'success' : 
                                   season.Status === 'PENDING' ? 'warning' : 'secondary';
                const statusText = season.Status === 'ACTIVE' ? 'Đang Active' : 
                                  season.Status === 'PENDING' ? 'Sắp Bắt Đầu' : 'Đã Kết Thúc';
                const statusIcon = season.Status === 'ACTIVE' ? 'fa-check-circle' : 
                                  season.Status === 'PENDING' ? 'fa-clock' : 'fa-check';
                
                const startDate = new Date(season.StartDate).toLocaleString('vi-VN');
                const endDate = season.EndDate ? new Date(season.EndDate).toLocaleString('vi-VN') : 'Chưa xác định';
                
                const actions = [];
                // Only allow editing when feature is disabled to avoid runtime conflicts
                if (!featureEnabled) {
                    actions.push(`<button class="btn btn-sm" onclick="editSeason(${season.Id})" style="padding: 5px 10px; font-size: 0.85rem;"><i class="fas fa-edit"></i> Sửa</button>`);
                }
                actions.push(`<button class="btn btn-sm btn-primary" onclick="viewSeasonDetail(${season.Id})" style="padding: 5px 10px; font-size: 0.85rem;"><i class="fas fa-eye"></i> Chi Tiết</button>`);
                
                const row = `
                    <tr>
                        <td>${idx + 1}</td>
                        <td>${escapeHtml(season.SeasonName)}</td>
                        <td>
                            <div>${startDate}</div>
                            <div>${endDate}</div>
                        </td>
                        <td>
                            <span class="alert alert-${statusClass}" style="padding: 4px 8px; font-size: 0.85rem;">
                                <i class="fas ${statusIcon}"></i> ${statusText}
                            </span>
                        </td>
                        <td>${(season.total_spins || 0).toLocaleString('vi-VN')}</td>
                        <td>${season.total_participants || 0}</td>
                        <td style="display:flex; gap:8px; flex-wrap: wrap;">${actions.join('')}</td>
                    </tr>
                `;
                
                tbody.append(row);
            });
        }
        
        // Open native datetime picker (Chrome supports showPicker; fallback to focus)
        function openDatePicker(inputId) {
            const el = document.getElementById(inputId);
            if (!el) return;
            if (typeof el.showPicker === 'function') {
                el.showPicker();
            } else {
                el.focus();
                el.click();
            }
        }
        
        // Open season modal for create
        function openSeasonModal() {
            $('#createSeasonForm')[0].reset();
            $('#createSeasonForm').removeData('edit-id');
            $('#seasonModalTitle').html('<i class="fas fa-plus-circle"></i> Tạo Mùa');
            $('#createSeasonForm button[type="submit"]').html('<i class="fas fa-save"></i> Lưu');
            $('#seasonModal').show();
        }
        
        // Create/Update season form submit (modal)
        $(document).on('submit', '#createSeasonForm', function(e) {
            e.preventDefault();
            
            const editId = $(this).data('edit-id');
            
            const formData = {
                season_name: $('#seasonName').val().trim(),
                // Season type is fixed to DAY (UI removed).
                season_type: 'DAY',
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val() || null
            };
            
            if (!formData.season_name || !formData.start_date) {
                showAlert('error', 'Vui lòng điền đầy đủ thông tin');
                return;
            }
            
            // If editing, add ID and use update endpoint
            if (editId) {
                formData.id = editId;
                var url = '/api/cms/lucky_wheel/update_season.php';
            } else {
                var url = '/api/cms/lucky_wheel/create_season.php';
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        $('#createSeasonForm')[0].reset();
                        $('#createSeasonForm').removeData('edit-id');
                        $('#seasonModalTitle').html('<i class="fas fa-plus-circle"></i> Tạo Mùa');
                        $('#createSeasonForm button[type="submit"]').html('<i class="fas fa-save"></i> Lưu');
                        closeModal('seasonModal');
                        loadSeasons();
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Lỗi kết nối');
                }
            });
        });
        
        // Edit season
        function editSeason(seasonId) {
            $.ajax({
                url: '/api/cms/lucky_wheel/get_season_detail.php?season_id=' + seasonId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const season = response.data.season;
                        $('#seasonName').val(season.SeasonName);
                        $('#startDate').val(season.StartDate.substring(0, 16));
                        $('#endDate').val(season.EndDate ? season.EndDate.substring(0, 16) : '');
                        
                        // Store season ID for update
                        $('#createSeasonForm').data('edit-id', seasonId);
                        $('#seasonModalTitle').html('<i class="fas fa-edit"></i> Chỉnh Sửa Mùa');
                        $('#createSeasonForm button[type="submit"]').html('<i class="fas fa-save"></i> Cập Nhật');
                        $('#seasonModal').show();
                    } else {
                        showAlert('error', response.error);
                    }
                }
            });
        }
        
        // View season detail
        function viewSeasonDetail(seasonId) {
            $.ajax({
                url: '/api/cms/lucky_wheel/get_season_detail.php?season_id=' + seasonId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const season = data.season;
                        const leaderboard = data.leaderboard;
                        const stats = data.stats;
                        
                        let html = `
                            <div style="background: rgba(20, 25, 40, 0.9); border: 2px solid #e8c088; border-radius: 8px; padding: 20px; max-width: 800px; margin: 20px auto;">
                                <h4 style="color: #e8c088; margin-bottom: 15px;">
                                    <i class="fas fa-info-circle"></i> Chi Tiết Mùa: ${escapeHtml(season.SeasonName)}
                                </h4>
                                <div style="color: #87ceeb; margin-bottom: 20px;">
                                    <div><strong>Bắt đầu:</strong> ${new Date(season.StartDate).toLocaleString('vi-VN')}</div>
                                    <div><strong>Kết thúc:</strong> ${season.EndDate ? new Date(season.EndDate).toLocaleString('vi-VN') : 'Chưa xác định'}</div>
                                    <div><strong>Trạng thái:</strong> ${season.Status === 'ACTIVE' ? 'Đang Active' : season.Status === 'PENDING' ? 'Sắp Bắt Đầu' : 'Đã Kết Thúc'}</div>
                                    <div><strong>Tổng người tham gia:</strong> ${stats.total_participants}</div>
                                    <div><strong>Tổng lượt quay:</strong> ${stats.total_spins}</div>
                                </div>
                                <h5 style="color: #e8c088; margin: 20px 0 10px 0;">Top 5 Người Chơi:</h5>
                                <div style="display: grid; gap: 10px;">
                        `;
                        
                        if (leaderboard && leaderboard.length > 0) {
                            leaderboard.forEach(function(player, index) {
                                html += `
                                    <div style="background: rgba(30, 35, 60, 0.8); padding: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                                            <span style="color: #e8c088; font-weight: bold; min-width: 30px;">${index + 1}</span>
                                            <span style="color: #87ceeb;">${escapeHtml(player.Username)}</span>
                                        </div>
                                        <div style="color: #ffd700; font-weight: bold; min-width: 80px; text-align: right;">${player.TotalSpins.toLocaleString()}</div>
                                    </div>
                                `;
                            });
                        } else {
                            html += '<div style="text-align: center; color: #87ceeb; padding: 20px;">Chưa có dữ liệu</div>';
                        }
                        
                        html += `
                                </div>
                                <div style="text-align: center; margin-top: 20px;">
                                    <button class="btn btn-secondary" onclick="closeSeasonDetail()" style="padding: 8px 20px;">
                                        <i class="fas fa-times"></i> Đóng
                                    </button>
                                </div>
                            </div>
                        `;
                        
                        // Create modal if not exists
                        if ($('#seasonDetailModal').length === 0) {
                            $('body').append('<div id="seasonDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; overflow-y: auto; padding: 20px;"></div>');
                        }
                        
                        $('#seasonDetailModal').html(html).show();
                    } else {
                        showAlert('error', response.error);
                    }
                }
            });
        }
        
        function closeSeasonDetail() {
            $('#seasonDetailModal').hide();
        }
        
        // Close modal on outside click
        $(document).on('click', '#seasonDetailModal', function(e) {
            if (e.target.id === 'seasonDetailModal') {
                closeSeasonDetail();
            }
        });
    </script>
</body>
</html>
