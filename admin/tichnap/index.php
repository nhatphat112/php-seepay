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
    <title>Quản Lý Mốc Nạp Tích Lũy - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .item-selector {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .item-search {
            margin-bottom: 15px;
        }
        
        .item-search input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
        }
        
        .item-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .item-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .item-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .item-card.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .item-card img {
            width: 100%;
            height: 120px;
            object-fit: contain;
            border-radius: 6px;
            background: #f5f5f5;
        }
        
        .item-card .item-name {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            text-align: center;
        }
        
        .item-card .item-code {
            font-size: 11px;
            color: #666;
            text-align: center;
            margin-top: 4px;
        }
        
        .item-input-row {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .item-input-row:first-child {
            border-color: #667eea;
        }
        
        .milestone-list {
            display: grid;
            gap: 15px;
        }
        
        .milestone-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: white;
        }
        
        .milestone-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .milestone-price {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
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
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .milestone-item img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-gift"></i> Quản Lý Mốc Nạp Tích Lũy</h1>
            <a href="../cms/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại CMS
            </a>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('milestones')">
                <i class="fas fa-list"></i> Danh Sách Mốc
            </button>
            <button class="tab" onclick="switchTab('create')">
                <i class="fas fa-plus"></i> Tạo Mốc Mới
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
        
        <!-- Tab: Cấu hình -->
        <div id="configTab" class="tab-content">
            <div style="max-width: 600px; margin: 0 auto;">
                <h2 style="margin-bottom: 30px; color: #333;">
                    <i class="fas fa-cog"></i> Cấu Hình Tính Năng
                </h2>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 18px;">
                        <input type="checkbox" id="featureToggle" style="width: 24px; height: 24px; cursor: pointer;">
                        <span>Bật/Tắt Tính Năng Nạp Tích Lũy</span>
                    </label>
                    <p style="color: #666; margin-top: 10px; font-size: 14px;">
                        Khi tắt, người dùng sẽ không thể nhận phần thưởng mốc nạp
                    </p>
                </div>
                
                <div class="form-group">
                    <label>Thời Gian Bắt Đầu Sự Kiện</label>
                    <input type="datetime-local" id="eventStartDate">
                    <p style="color: #888; margin-top: 5px; font-size: 12px;">
                        Để trống nếu muốn bắt đầu ngay lập tức
                    </p>
                </div>

                <div class="form-group">
                    <label>Thời Gian Kết Thúc Sự Kiện</label>
                    <input type="datetime-local" id="eventEndDate">
                    <p style="color: #888; margin-top: 5px; font-size: 12px;">
                        Để trống nếu sự kiện không giới hạn thời gian
                    </p>
                </div>
                
                <div class="form-group">
                    <button onclick="saveConfig()" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu Cấu Hình
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Tab: Tạo mốc mới -->
        <div id="createTab" class="tab-content">
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
                        <div class="item-input-row" style="display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: end;">
                            <div>
                                <label style="font-size: 12px; color: #666; margin-bottom: 5px;">Tên Vật Phẩm</label>
                                <input type="text" class="item-name" placeholder="Ví dụ: Quiver" required>
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #666; margin-bottom: 5px;">ID Vật Phẩm (CodeItem)</label>
                                <input type="text" class="item-code" placeholder="Ví dụ: ITEM_MALL_QUIVER" required>
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #666; margin-bottom: 5px;">Số Lượng</label>
                                <input type="number" class="item-quantity" placeholder="1" value="1" min="1" required>
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary" onclick="removeItemRow(this)" style="padding: 12px 15px;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addItemRow()" style="margin-top: 10px;">
                        <i class="fas fa-plus"></i> Thêm Vật Phẩm
                    </button>
                </div>
                
                <div class="form-group">
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
    
    <script>
        // Thêm dòng nhập item mới
        function addItemRow() {
            const container = document.getElementById('itemsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'item-input-row';
            newRow.style.cssText = 'display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: end; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 2px solid #e0e0e0;';
            newRow.innerHTML = `
                <div>
                    <label style="font-size: 12px; color: #666; margin-bottom: 5px; display: block;">Tên Vật Phẩm</label>
                    <input type="text" class="item-name" placeholder="Ví dụ: Quiver" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: #666; margin-bottom: 5px; display: block;">ID Vật Phẩm (CodeItem)</label>
                    <input type="text" class="item-code" placeholder="Ví dụ: ITEM_MALL_QUIVER" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: #666; margin-bottom: 5px; display: block;">Số Lượng</label>
                    <input type="number" class="item-quantity" placeholder="1" value="1" min="1" required>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="removeItemRow(this)" style="padding: 12px 15px;">
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
                alert('Phải có ít nhất một vật phẩm!');
            }
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
                alert('Lỗi tải cấu hình: ' + error.message);
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
                    alert(result.message || 'Đã lưu cấu hình thành công!');
                } else {
                    alert('Lỗi: ' + (result.error || 'Không thể lưu cấu hình'));
                }
            } catch (error) {
                alert('Lỗi: ' + error.message);
            }
        }

        // Switch tabs
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tabName === 'milestones') {
                document.querySelector('.tab').classList.add('active');
                document.getElementById('milestonesTab').classList.add('active');
                loadMilestones();
            } else if (tabName === 'config') {
                document.querySelectorAll('.tab')[2].classList.add('active');
                document.getElementById('configTab').classList.add('active');
                loadConfig();
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('createTab').classList.add('active');
            }
        }
        
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
                                    ${milestone.description ? `<div style="color: #666; margin-top: 5px;">${milestone.description}</div>` : ''}
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-secondary" onclick="deleteMilestone('${milestone.id}')" style="font-size: 12px; padding: 6px 12px;">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>
                            </div>
                            <div class="milestone-items">
                                ${milestone.items.map(item => `
                                    <div class="milestone-item">
                                        ${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<div style="width: 40px; height: 40px; background: #e0e0e0; border-radius: 4px;"></div>'}
                                        <div>
                                            <div style="font-weight: 600; font-size: 12px;">${item.name}</div>
                                            <div style="font-size: 11px; color: #666;">${item.key}</div>
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
                    alert('Vui lòng điền đầy đủ thông tin vật phẩm!');
                    return;
                }
                
                items.push({
                    name: name,
                    codeItem: code,
                    quantity: quantity
                });
            }
            
            if (items.length === 0) {
                alert('Vui lòng thêm ít nhất một vật phẩm phần thưởng!');
                return;
            }
            
            const rank = parseInt(document.getElementById('rank').value);
            const description = document.getElementById('description').value;
            
            if (!rank || rank <= 0) {
                alert('Vui lòng nhập mốc tiền hợp lệ!');
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
                    alert('Đã tạo mốc nạp thành công!');
                    resetForm();
                    switchTab('milestones');
                } else {
                    alert('Lỗi: ' + result.error);
                }
            } catch (error) {
                alert('Lỗi: ' + error.message);
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
                    alert('Đã xóa mốc nạp thành công!');
                    loadMilestones();
                } else {
                    alert('Lỗi: ' + result.error);
                }
            } catch (error) {
                alert('Lỗi: ' + error.message);
            }
        }
        
        // Load milestones on page load
        loadMilestones();
    </script>
</body>
</html>

