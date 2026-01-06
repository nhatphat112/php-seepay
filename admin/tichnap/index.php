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
        
        .selected-items {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .selected-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            margin: 5px;
            border: 2px solid #667eea;
        }
        
        .selected-item .remove-btn {
            cursor: pointer;
            color: #dc3545;
            font-weight: bold;
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
                    <label>Chọn Vật Phẩm Phần Thưởng *</label>
                    <div class="item-selector">
                        <div class="item-search">
                            <input type="text" id="itemSearch" placeholder="Tìm kiếm vật phẩm (mã hoặc tên)...">
                        </div>
                        <div id="itemList" class="item-list">
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin"></i> Đang tải vật phẩm...
                            </div>
                        </div>
                    </div>
                    <div id="selectedItems" class="selected-items" style="display: none;">
                        <strong>Đã chọn:</strong>
                        <div id="selectedItemsList"></div>
                    </div>
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
        let selectedItems = [];
        let allItems = [];
        
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
                loadItems();
            }
        }
        
        // Load milestones
        async function loadMilestones() {
            const listEl = document.getElementById('milestonesList');
            listEl.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
            
            try {
                const response = await fetch('../../api/tichnap/get_ranks.php');
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    listEl.innerHTML = result.data.map(milestone => `
                        <div class="milestone-card" style="${milestone.isActive ? 'border-color: #4caf50; background: #f0f9f0;' : ''}">
                            <div class="milestone-header">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="milestone-price">${milestone.price}</div>
                                        ${milestone.isActive ? '<span style="background: #4caf50; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">ĐANG KÍCH HOẠT</span>' : ''}
                                    </div>
                                    ${milestone.description ? `<div style="color: #666; margin-top: 5px;">${milestone.description}</div>` : ''}
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    ${!milestone.isActive ? `<button class="btn btn-primary" onclick="activateMilestone('${milestone.id}')" style="font-size: 12px; padding: 6px 12px;">
                                        <i class="fas fa-check"></i> Kích Hoạt
                                    </button>` : ''}
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
        
        // Load items for selection
        async function loadItems(keyword = '') {
            const listEl = document.getElementById('itemList');
            listEl.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
            
            try {
                const url = `../../api/tichnap/search_items.php?limit=50${keyword ? '&keyword=' + encodeURIComponent(keyword) : ''}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    allItems = result.data;
                    listEl.innerHTML = result.data.map(item => `
                        <div class="item-card" onclick="toggleItem('${item.id}', '${item.codeItem}', '${item.nameItem}', '${item.image || ''}')">
                            ${item.image ? `<img src="${item.image}" alt="${item.nameItem}">` : '<div style="width: 100%; height: 120px; background: #f5f5f5; border-radius: 6px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image" style="font-size: 32px; color: #ccc;"></i></div>'}
                            <div class="item-name">${item.nameItem}</div>
                            <div class="item-code">${item.codeItem}</div>
                        </div>
                    `).join('');
                } else {
                    listEl.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i><p>Không tìm thấy vật phẩm</p></div>';
                }
            } catch (error) {
                listEl.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Lỗi: ${error.message}</p></div>`;
            }
        }
        
        // Toggle item selection
        function toggleItem(id, codeItem, nameItem, image) {
            const index = selectedItems.findIndex(item => item.id === id);
            
            if (index > -1) {
                selectedItems.splice(index, 1);
            } else {
                selectedItems.push({ id, codeItem, nameItem, image });
            }
            
            updateSelectedItems();
            updateItemCards();
        }
        
        // Update selected items display
        function updateSelectedItems() {
            const container = document.getElementById('selectedItems');
            const listEl = document.getElementById('selectedItemsList');
            
            if (selectedItems.length > 0) {
                container.style.display = 'block';
                listEl.innerHTML = selectedItems.map(item => `
                    <div class="selected-item">
                        ${item.image ? `<img src="${item.image}" style="width: 30px; height: 30px; object-fit: contain;">` : ''}
                        <span>${item.nameItem}</span>
                        <span class="remove-btn" onclick="removeItem('${item.id}')">×</span>
                    </div>
                `).join('');
            } else {
                container.style.display = 'none';
            }
        }
        
        // Update item cards visual state
        function updateItemCards() {
            document.querySelectorAll('.item-card').forEach(card => {
                const itemId = card.getAttribute('onclick').match(/'([^']+)'/)[1];
                if (selectedItems.find(item => item.id === itemId)) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        }
        
        // Remove item from selection
        function removeItem(id) {
            selectedItems = selectedItems.filter(item => item.id !== id);
            updateSelectedItems();
            updateItemCards();
        }
        
        // Search items
        let searchTimeout;
        document.getElementById('itemSearch').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadItems(e.target.value);
            }, 300);
        });
        
        // Create milestone form
        document.getElementById('createMilestoneForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (selectedItems.length === 0) {
                alert('Vui lòng chọn ít nhất một vật phẩm phần thưởng!');
                return;
            }
            
            const rank = parseInt(document.getElementById('rank').value);
            const description = document.getElementById('description').value;
            const itemIds = selectedItems.map(item => item.id);
            
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
                        itemIds: itemIds
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
            selectedItems = [];
            updateSelectedItems();
            updateItemCards();
        }
        
        // Activate milestone
        async function activateMilestone(id) {
            if (!confirm('Bạn có chắc muốn kích hoạt mốc nạp này? Mốc đang active sẽ bị tắt.')) {
                return;
            }
            
            try {
                const response = await fetch('../../api/tichnap/activate_milestone.php', {
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
                    alert('Đã kích hoạt mốc nạp thành công!');
                    loadMilestones();
                } else {
                    alert('Lỗi: ' + result.error);
                }
            } catch (error) {
                alert('Lỗi: ' + error.message);
            }
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

