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
            
            <!-- Alert Messages -->
            <div id="alertMessage" class="alert" style="display: none;"></div>
            
            <!-- Items Management -->
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="color: #e8c088; margin: 0;">
                        <i class="fas fa-list"></i> Danh Sách Vật Phẩm
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
        </main>
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
                    <small>Ví dụ: iPhone 16 Pro, MacBook Air, Voucher 500K</small>
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
                        <input type="checkbox" id="isRare" style="width: auto; margin-right: 8px;">
                        Vật phẩm hiếm
                    </label>
                    <small>Đánh dấu vật phẩm hiếm sẽ hiển thị trên ticker trang chủ</small>
                </div>
                
                <div class="form-group">
                    <label for="winRate">Tỉ lệ quay ra (%) *</label>
                    <input type="number" id="winRate" required min="0.01" max="100" step="0.01" placeholder="0.00">
                    <small>Tỉ lệ phần trăm để quay ra vật phẩm này (0.01 - 100)</small>
                </div>
                
                <div class="form-group">
                    <label for="displayOrder">Thứ tự hiển thị</label>
                    <input type="number" id="displayOrder" min="0" value="0">
                    <small>Thứ tự hiển thị trên vòng quay (số nhỏ hơn hiển thị trước)</small>
                </div>
                
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
        // Load config and items on page load
        $(document).ready(function() {
            loadConfig();
            loadItems();
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
            
            $.ajax({
                url: '/api/cms/lucky_wheel/get_items.php?include_inactive=1',
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
                const rareClass = item.IsRare ? 'rare-yes' : 'rare-no';
                const rareText = item.IsRare ? 'Có' : 'Không';
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
            $('#displayOrder').val(0);
            $('#isRare').prop('checked', false);
            $('#itemModal').show();
        }
        
        // Open edit item modal
        function openEditItemModal(itemId) {
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
                            $('#isRare').prop('checked', item.IsRare);
                            $('#winRate').val(item.WinRate);
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
                win_rate: parseFloat($('#winRate').val()),
                display_order: parseInt($('#displayOrder').val() || 0)
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
