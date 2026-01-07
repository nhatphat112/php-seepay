<?php
/**
 * Admin: Quản lý Mốc Nạp Tích Lũy
 * Trang quản lý cấu hình mốc nạp và phần thưởng
 */

require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../connection_manager.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mốc Nạp Tích Lũy - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../admin_common.css">
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .item-input-row {
            padding: 15px;
            background: #0f1624;
            border-radius: 8px;
            border: 1px solid #4682b4;
            display: grid;
            grid-template-columns: 2fr 2fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: end;
        }
        
        .milestone-list {
            display: grid;
            gap: 15px;
        }
        
        .milestone-card {
            background: #16213e;
            border: 1px solid #0f1624;
            border-radius: 10px;
            padding: 20px;
        }
        
        .milestone-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #0f1624;
        }
        
        .milestone-price {
            font-size: 20px;
            font-weight: bold;
            color: #e8c088;
        }
        
        .milestone-items {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .milestone-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #0f1624;
            border-radius: 6px;
            border: 1px solid #4682b4;
        }
        
        .milestone-item img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #87ceeb;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #4682b4;
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
                <li><a href="../slider.php"><i class="fas fa-images"></i> Slider (5 ảnh)</a></li>
                <li><a href="../news.php"><i class="fas fa-newspaper"></i> Tin Bài</a></li>
                <li><a href="../social.php"><i class="fas fa-share-alt"></i> Social Links</a></li>
                <li><a href="../server_info.php"><i class="fas fa-server"></i> Thông Tin Server</a></li>
                <li><a href="../weekly_events.php"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="../qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="../orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="index.php" class="active"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</h2>
                <p>Quản lý mốc nạp tích lũy và phần thưởng</p>
            </div>
            
            <div class="alert alert-success" id="alertSuccess" style="display: none;"></div>
            <div class="alert alert-error" id="alertError" style="display: none;"></div>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('milestones')">
                <i class="fas fa-list"></i> Danh Sách Mốc
            </button>
            <button class="tab" onclick="switchTab('create')">
                <i class="fas fa-plus"></i> Tạo Mốc Mới
            </button>
            <button class="tab" onclick="switchTab('manage')">
                <i class="fas fa-users-cog"></i> Quản Lý Tích Lũy
            </button>
            <button class="tab" onclick="switchTab('config')">
                <i class="fas fa-cog"></i> Cấu Hình
            </button>
        </div>
        
        <!-- Tab: Danh sách mốc -->
        <div id="milestonesTab" class="tab-content active">
            <div id="milestonesList" class="milestone-list">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                </div>
            </div>
        </div>
        
            <!-- Tab: Quản Lý Tích Lũy -->
            <div id="manageTab" class="tab-content">
                <div class="form-container" style="max-width: 800px;">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                        <i class="fas fa-users-cog"></i> Quản Lý Tích Lũy
                    </h3>
                    
                    <!-- Reset Tích Lũy -->
                    <div style="background: #0f1624; padding: 20px; border-radius: 10px; margin-bottom: 30px; border: 1px solid #4682b4;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-redo"></i> Reset Tích Lũy
                        </h4>
                        <form id="resetTotalMoneyForm">
                            <div class="form-group">
                                <label>Chọn đối tượng</label>
                                <select id="resetTarget" name="target" required>
                                    <option value="all">Tất cả người dùng</option>
                                    <option value="user">Người dùng cụ thể</option>
                                </select>
                            </div>
                            <div class="form-group" id="resetUsernameGroup" style="display: none;">
                                <label>Username</label>
                                <input type="text" id="resetUsername" name="username" placeholder="Nhập username">
                                <small>Nhập username của người dùng cần reset</small>
                            </div>
                            <div class="btn-group">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-redo"></i> Reset Tích Lũy
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Cộng Tích Lũy -->
                    <div style="background: #0f1624; padding: 20px; border-radius: 10px; border: 1px solid #4682b4;">
                        <h4 style="color: #e8c088; margin-bottom: 15px;">
                            <i class="fas fa-plus-circle"></i> Cộng Tích Lũy
                        </h4>
                        <form id="addTotalMoneyForm">
                            <div class="form-group">
                                <label>Chọn đối tượng</label>
                                <select id="addTarget" name="target" required>
                                    <option value="all">Tất cả người dùng</option>
                                    <option value="user">Người dùng cụ thể</option>
                                </select>
                            </div>
                            <div class="form-group" id="addUsernameGroup" style="display: none;">
                                <label>Username</label>
                                <input type="text" id="addUsername" name="username" placeholder="Nhập username">
                                <small>Nhập username của người dùng cần cộng tích lũy</small>
                            </div>
                            <div class="form-group">
                                <label>Số Tiền Cộng (VND) *</label>
                                <input type="number" id="addAmount" name="amount" required min="1" placeholder="100000">
                                <small>Số tiền tích lũy sẽ được cộng thêm</small>
                            </div>
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Cộng Tích Lũy
                                </button>
                            </div>
                        </form>
                </div>
            </div>
        </div>
        
        <!-- Tab: Cấu hình -->
        <div id="configTab" class="tab-content">
                <div class="form-container">
                    <h3 style="color: #e8c088; margin-bottom: 20px;">
                    <i class="fas fa-cog"></i> Cấu Hình Tính Năng
                    </h3>
                
                <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; font-size: 16px;">
                            <input type="checkbox" id="featureToggle" style="width: 20px; height: 20px; cursor: pointer;">
                        <span>Bật/Tắt Tính Năng Nạp Tích Lũy</span>
                    </label>
                        <small>Khi tắt, người dùng sẽ không thể nhận phần thưởng mốc nạp</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Thời Gian Bắt Đầu Sự Kiện</label>
                        <input type="datetime-local" id="eventStartDate">
                        <small>Để trống nếu muốn bắt đầu ngay lập tức</small>
                </div>
                
                <div class="form-group">
                        <label>Thời Gian Kết Thúc Sự Kiện</label>
                        <input type="datetime-local" id="eventEndDate">
                        <small>Để trống nếu sự kiện không giới hạn thời gian</small>
                    </div>
                    
                    <div class="btn-group">
                    <button onclick="saveConfig()" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu Cấu Hình
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Tab: Tạo mốc mới -->
        <div id="createTab" class="tab-content">
                <div class="form-container">
            <form id="createMilestoneForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Mốc Tiền (VND) *</label>
                        <input type="number" id="rank" name="rank" required min="0" placeholder="100000">
                    </div>
                    <div class="form-group">
                        <label>Mô Tả</label>
                        <input type="text" id="description" name="description" placeholder="Phần thưởng mốc 100k">
                    </div>
                </div>
                
                <div class="form-group">
                            <label>Vật Phẩm Phần Thưởng *</label>
                            <div id="itemsContainer">
                                <div class="item-input-row">
                                    <div>
                                        <label style="font-size: 12px; color: #87ceeb; margin-bottom: 5px; display: block;">Tên Vật Phẩm</label>
                                        <input type="text" class="item-name" placeholder="Ví dụ: Quiver" required>
                                    </div>
                                    <div>
                                        <label style="font-size: 12px; color: #87ceeb; margin-bottom: 5px; display: block;">ID Vật Phẩm (CodeItem)</label>
                                        <input type="text" class="item-code" placeholder="Ví dụ: ITEM_MALL_QUIVER" required>
                                    </div>
                                    <div>
                                        <label style="font-size: 12px; color: #87ceeb; margin-bottom: 5px; display: block;">Số Lượng</label>
                                        <input type="number" class="item-quantity" placeholder="1" value="1" min="1" required>
                        </div>
                                    <div>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                            </div>
                        </div>
                    </div>
                            <button type="button" class="btn btn-secondary" onclick="addItemRow()" style="margin-top: 10px;">
                                <i class="fas fa-plus"></i> Thêm Vật Phẩm
                            </button>
                </div>
                
                        <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Tạo Mốc Nạp
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </form>
        </div>
            </div>
        </main>
    </div>
    
    <script>
        // Thêm dòng nhập item mới
        function addItemRow() {
            const container = document.getElementById('itemsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'item-input-row';
            newRow.innerHTML = `
                <div>
                    <label style="font-size: 12px; color: #87ceeb; margin-bottom: 5px; display: block;">Tên Vật Phẩm</label>
                    <input type="text" class="item-name" placeholder="Ví dụ: Quiver" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: #87ceeb; margin-bottom: 5px; display: block;">ID Vật Phẩm (CodeItem)</label>
                    <input type="text" class="item-code" placeholder="Ví dụ: ITEM_MALL_QUIVER" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: #87ceeb; margin-bottom: 5px; display: block;">Số Lượng</label>
                    <input type="number" class="item-quantity" placeholder="1" value="1" min="1" required>
                </div>
                <div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }
        
        // Xóa dòng nhập item
        function removeItemRow(btn) {
            const container = document.getElementById('itemsContainer');
            if (container.children.length > 1) {
                btn.closest('.item-input-row').remove();
            } else {
                showAlert('Phải có ít nhất một vật phẩm!', 'error');
            }
        }
        
        // Show alert
        function showAlert(message, type = 'success') {
            const alertEl = type === 'success' ? document.getElementById('alertSuccess') : document.getElementById('alertError');
            const otherEl = type === 'success' ? document.getElementById('alertError') : document.getElementById('alertSuccess');
            alertEl.textContent = message;
            alertEl.style.display = 'block';
            otherEl.style.display = 'none';
            setTimeout(() => {
                alertEl.style.display = 'none';
            }, 5000);
        }
        
        // Load config (feature toggle + thời gian sự kiện)
        async function loadConfig() {
            try {
                const res = await fetch('../../api/tichnap/get_config.php');
                const result = await res.json();
                if (result.success && result.data) {
                    document.getElementById('featureToggle').checked = !!result.data.featureEnabled;
                    
                    // Parse datetime từ server (format DATETIME) sang datetime-local (YYYY-MM-DDTHH:MM)
                    if (result.data.eventStartDate) {
                        const start = new Date(result.data.eventStartDate);
                        document.getElementById('eventStartDate').value = start.toISOString().slice(0, 16);
                    } else {
                        document.getElementById('eventStartDate').value = '';
                    }
                    
                    if (result.data.eventEndDate) {
                        const end = new Date(result.data.eventEndDate);
                        document.getElementById('eventEndDate').value = end.toISOString().slice(0, 16);
                    } else {
                        document.getElementById('eventEndDate').value = '';
                    }
                }
            } catch (error) {
                showAlert('Lỗi tải cấu hình: ' + error.message, 'error');
            }
        }

        // Save config (feature toggle + thời gian sự kiện)
        async function saveConfig() {
            const featureEnabled = document.getElementById('featureToggle').checked;
            const eventStartDate = document.getElementById('eventStartDate').value || null;
            const eventEndDate = document.getElementById('eventEndDate').value || null;
            try {
                const res = await fetch('../../api/tichnap/update_config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        featureEnabled,
                        eventStartDate,
                        eventEndDate
                    })
                });
                const result = await res.json();
                if (result.success) {
                    showAlert(result.message || 'Đã lưu cấu hình thành công!', 'success');
                } else {
                    showAlert('Lỗi: ' + (result.error || 'Không thể lưu cấu hình'), 'error');
                }
            } catch (error) {
                showAlert('Lỗi: ' + error.message, 'error');
            }
        }
        
        // Switch tabs
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tabName === 'milestones') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('milestonesTab').classList.add('active');
                loadMilestones();
            } else if (tabName === 'config') {
                document.querySelectorAll('.tab')[3].classList.add('active');
                document.getElementById('configTab').classList.add('active');
                loadConfig();
            } else if (tabName === 'manage') {
                document.querySelectorAll('.tab')[2].classList.add('active');
                document.getElementById('manageTab').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('createTab').classList.add('active');
            }
        }
        
        // Toggle username input based on target selection
        document.getElementById('resetTarget').addEventListener('change', function() {
            const usernameGroup = document.getElementById('resetUsernameGroup');
            if (this.value === 'user') {
                usernameGroup.style.display = 'block';
                document.getElementById('resetUsername').required = true;
            } else {
                usernameGroup.style.display = 'none';
                document.getElementById('resetUsername').required = false;
                document.getElementById('resetUsername').value = '';
            }
        });
        
        document.getElementById('addTarget').addEventListener('change', function() {
            const usernameGroup = document.getElementById('addUsernameGroup');
            if (this.value === 'user') {
                usernameGroup.style.display = 'block';
                document.getElementById('addUsername').required = true;
            } else {
                usernameGroup.style.display = 'none';
                document.getElementById('addUsername').required = false;
                document.getElementById('addUsername').value = '';
            }
        });
        
        // Reset Total Money Form
        document.getElementById('resetTotalMoneyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const target = document.getElementById('resetTarget').value;
            const username = document.getElementById('resetUsername').value.trim();
            
            if (target === 'user' && !username) {
                showAlert('Vui lòng nhập username!', 'error');
                return;
            }
            
            if (!confirm(`Bạn có chắc muốn reset tích lũy ${target === 'all' ? 'cho tất cả người dùng' : 'cho user ' + username}?`)) {
                return;
            }
            
            try {
                const res = await fetch('../../api/tichnap/reset_total_money.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        target: target,
                        username: target === 'user' ? username : null
                    })
                });
                
                const result = await res.json();
                if (result.success) {
                    showAlert(result.message || 'Đã reset tích lũy thành công!', 'success');
                    document.getElementById('resetTotalMoneyForm').reset();
                    document.getElementById('resetUsernameGroup').style.display = 'none';
                } else {
                    showAlert('Lỗi: ' + (result.error || 'Không thể reset tích lũy'), 'error');
                }
            } catch (error) {
                showAlert('Lỗi: ' + error.message, 'error');
            }
        });
        
        // Add Total Money Form
        document.getElementById('addTotalMoneyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const target = document.getElementById('addTarget').value;
            const username = document.getElementById('addUsername').value.trim();
            const amount = parseInt(document.getElementById('addAmount').value);
            
            if (target === 'user' && !username) {
                showAlert('Vui lòng nhập username!', 'error');
                return;
            }
            
            if (!amount || amount <= 0) {
                showAlert('Vui lòng nhập số tiền hợp lệ!', 'error');
                return;
            }
            
            if (!confirm(`Bạn có chắc muốn cộng ${amount.toLocaleString('vi-VN')} VND tích lũy ${target === 'all' ? 'cho tất cả người dùng' : 'cho user ' + username}?`)) {
                return;
            }
            
            try {
                const res = await fetch('../../api/tichnap/add_total_money.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        target: target,
                        username: target === 'user' ? username : null,
                        amount: amount
                    })
                });
                
                const result = await res.json();
                if (result.success) {
                    showAlert(result.message || 'Đã cộng tích lũy thành công!', 'success');
                    document.getElementById('addTotalMoneyForm').reset();
                    document.getElementById('addUsernameGroup').style.display = 'none';
                } else {
                    showAlert('Lỗi: ' + (result.error || 'Không thể cộng tích lũy'), 'error');
                }
            } catch (error) {
                showAlert('Lỗi: ' + error.message, 'error');
            }
        });
        
        // Load milestones
        async function loadMilestones() {
            const listEl = document.getElementById('milestonesList');
            listEl.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
            
            try {
                // Dùng get_all_milestones để lấy tất cả mốc (bao gồm cả inactive nếu có)
                const response = await fetch('../../api/tichnap/get_all_milestones.php');
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    listEl.innerHTML = result.data.map(milestone => `
                        <div class="milestone-card">
                            <div class="milestone-header">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="milestone-price">${milestone.price}</div>
                                    </div>
                                    ${milestone.description ? `<div style="color: #87ceeb; margin-top: 5px;">${milestone.description}</div>` : ''}
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-danger btn-sm" onclick="deleteMilestone('${milestone.id}')">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>
                            </div>
                            <div class="milestone-items">
                                ${milestone.items.map(item => `
                                    <div class="milestone-item">
                                        ${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<div style="width: 40px; height: 40px; background: #0f1624; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #87ceeb;"><i class="fas fa-box"></i></div>'}
                                        <div>
                                            <div style="font-weight: 600; font-size: 12px; color: #e8c088;">${item.name}</div>
                                            <div style="font-size: 11px; color: #87ceeb;">${item.key}</div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `).join('');
                } else {
                    listEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>Chưa có mốc nạp nào</p></div>';
                }
            } catch (error) {
                listEl.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Lỗi: ${error.message}</p></div>`;
            }
        }
        
        
        // Create milestone form
        document.getElementById('createMilestoneForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Thu thập dữ liệu từ các dòng nhập item
            const itemRows = document.querySelectorAll('.item-input-row');
            const items = [];
            
            for (let row of itemRows) {
                const name = row.querySelector('.item-name').value.trim();
                const code = row.querySelector('.item-code').value.trim();
                const quantity = parseInt(row.querySelector('.item-quantity').value) || 1;
                
                if (!name || !code) {
                    showAlert('Vui lòng điền đầy đủ thông tin vật phẩm!', 'error');
                    return;
                }
                
                items.push({
                    name: name,
                    codeItem: code,
                    quantity: quantity
                });
            }
            
            if (items.length === 0) {
                showAlert('Vui lòng thêm ít nhất một vật phẩm phần thưởng!', 'error');
                return;
            }
            
            const rank = parseInt(document.getElementById('rank').value);
            const description = document.getElementById('description').value;
            
            if (!rank || rank <= 0) {
                showAlert('Vui lòng nhập mốc tiền hợp lệ!', 'error');
                return;
            }
            
            try {
                const response = await fetch('../../api/tichnap/create_milestone.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        rank: rank,
                        description: description,
                        items: items
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Đã tạo mốc nạp thành công!', 'success');
                    resetForm();
                    switchTab('milestones');
                } else {
                    showAlert('Lỗi: ' + result.error, 'error');
                }
            } catch (error) {
                showAlert('Lỗi: ' + error.message, 'error');
            }
        });
        
        // Reset form
        function resetForm() {
            document.getElementById('createMilestoneForm').reset();
            const container = document.getElementById('itemsContainer');
            // Giữ lại 1 dòng, xóa các dòng khác
            while (container.children.length > 1) {
                container.removeChild(container.lastChild);
            }
            // Reset dòng đầu tiên
            const firstRow = container.querySelector('.item-input-row');
            firstRow.querySelector('.item-name').value = '';
            firstRow.querySelector('.item-code').value = '';
            firstRow.querySelector('.item-quantity').value = '1';
        }
        
        // Delete milestone
        async function deleteMilestone(id) {
            if (!confirm('Bạn có chắc muốn xóa mốc nạp này?')) {
                return;
            }
            
            try {
                const response = await fetch('../../api/tichnap/delete_milestone.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Đã xóa mốc nạp thành công!', 'success');
                    loadMilestones();
                } else {
                    showAlert('Lỗi: ' + result.error, 'error');
                }
            } catch (error) {
                showAlert('Lỗi: ' + error.message, 'error');
            }
        }
        
        // Load milestones on page load
        loadMilestones();
    </script>
</body>
</html>
