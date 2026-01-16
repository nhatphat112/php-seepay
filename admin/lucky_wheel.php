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
                <button class="tab" onclick="switchTab('wheel-items')">
                    <i class="fas fa-list"></i> Vật Phẩm Vòng Quay
                </button>
                <button class="tab" onclick="switchTab('accumulated-items')">
                    <i class="fas fa-gift"></i> Vật Phẩm Mốc Quay
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
    </script>
</body>
</html>
