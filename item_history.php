<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/auth_helper.php';

$username = $_SESSION['username'] ?? 'Player';
$email = $_SESSION['email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = getUserRole();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Nhận Vật Phẩm</title>
    
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
        /* Fix scroll - allow scrolling when content is longer */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        
        body.home-page {
            overflow: hidden;
        }
        
        /* Dashboard layout with sidebar */
        .dashboard-wrapper {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(15px);
            z-index: 9999;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Sidebar */
        .dashboard-sidebar {
            width: 260px;
            background: rgba(22, 33, 62, 0.95);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 2px solid #1e90ff;
            z-index: 10000;
            -webkit-overflow-scrolling: touch;
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
        
        /* Mobile menu toggle */
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
        
        .menu-toggle:hover {
            background: rgba(30, 144, 255, 0.3);
        }
        
        /* Main content */
        .page-container {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            padding: 40px;
            box-sizing: border-box;
            background: rgba(9, 19, 38, 0.95);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(30, 144, 255, 0.3);
        }
        
        .page-title {
            font-size: 2rem;
            color: #ffd700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Item Filters */
        .item-filters {
            background: rgba(20, 30, 50, 0.8);
            border: 2px solid #4682b4;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-input,
        .filter-select {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
        }
        
        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #1e90ff;
            background: rgba(255, 255, 255, 0.12);
        }
        
        .filter-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #1e90ff 0%, #00bfff 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 144, 255, 0.4);
        }
        
        .filter-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        /* Item Container */
        .item-container {
            background: rgba(20, 30, 50, 0.8);
            border: 2px solid #4682b4;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }
        
        .loading-items {
            text-align: center;
            padding: 40px;
            color: #87ceeb;
            font-size: 16px;
        }
        
        .item-table {
            width: 100%;
            border-collapse: collapse;
            color: #87ceeb;
        }
        
        .item-table thead {
            background: rgba(30, 144, 255, 0.1);
        }
        
        .item-table th {
            padding: 12px;
            text-align: center;
            font-weight: 600;
            color: #1e90ff;
            border-bottom: 2px solid #1e90ff;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .item-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }
        
        .item-table tbody tr:hover {
            background: rgba(30, 144, 255, 0.05);
        }
        
        .source-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .source-lucky-wheel {
            background: #ffd700;
            color: #1a1a2e;
        }
        
        .source-accumulated {
            background: #87ceeb;
            color: #1a1a2e;
        }
        
        .item-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: rgba(20, 30, 50, 0.8);
            border: 2px solid #4682b4;
            border-radius: 12px;
        }
        
        .pagination-btn {
            padding: 8px 15px;
            background: rgba(30, 144, 255, 0.2);
            border: 1px solid #4682b4;
            color: #87ceeb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: rgba(30, 144, 255, 0.3);
            border-color: #1e90ff;
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-info {
            color: #87ceeb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .page-input {
            width: 50px;
            padding: 5px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 5px;
            color: #fff;
            text-align: center;
            font-size: 14px;
        }
        
        .page-input:focus {
            outline: none;
            border-color: #1e90ff;
        }
        
        #noItems {
            text-align: center;
            padding: 40px;
            color: #87ceeb;
        }
        
        #noItems i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #4682b4;
        }
        
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .dashboard-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .dashboard-sidebar.open {
                transform: translateX(0);
            }
            
            .page-container {
                margin-left: 0;
                max-width: 100%;
                padding: 60px 15px 30px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .filter-row {
                flex-direction: column;
            }
            
            .filter-input,
            .filter-select {
                width: 100%;
                min-width: 100%;
            }
            
            .item-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .item-table th,
            .item-table td {
                padding: 8px 5px;
                white-space: nowrap;
            }
            
            .item-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
    
    <!-- jQuery -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
</head>
<body class="home-page">
    <!-- Dashboard Wrapper -->
    <div class="dashboard-wrapper">
        <!-- Menu Toggle for Mobile -->
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="dashboardSidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-user-circle"></i> Dashboard</h1>
                <p><?php echo htmlspecialchars($username); ?></p>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="<?php echo getNavActiveClass('dashboard.php'); ?>"><i class="fas fa-home"></i> Trang Chủ</a></li>
                <li><a href="transaction_history.php" class="<?php echo getNavActiveClass('transaction_history.php'); ?>"><i class="fas fa-history"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="payment.php" class="<?php echo getNavActiveClass('payment.php'); ?>"><i class="fas fa-credit-card"></i> Nạp Tiền</a></li>
                <li><a href="tichnap.php" class="<?php echo getNavActiveClass('tichnap.php'); ?>"><i class="fas fa-gift"></i> Nạp Tích Lũy</a></li>
                <li><a href="lucky_wheel.php" class="<?php echo getNavActiveClass('lucky_wheel.php'); ?>"><i class="fas fa-dharmachakra"></i> Vòng Quay May Mắn</a></li>
                <li><a href="item_history.php" class="<?php echo getNavActiveClass('item_history.php'); ?>"><i class="fas fa-box-open"></i> Lịch Sử Nhận Vật Phẩm</a></li>
                <li><a href="download.php" class="<?php echo getNavActiveClass('download.php'); ?>"><i class="fas fa-download"></i> Tải Game</a></li>
                <li><a href="ranking.php" class="<?php echo getNavActiveClass('ranking.php'); ?>"><i class="fas fa-trophy"></i> Xếp Hạng</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin/cms/index.php" class="<?php echo getNavActiveClass('admin/cms/index.php'); ?>"><i class="fas fa-cog"></i> CMS Admin</a></li>
                <?php endif; ?>
                <li><a href="index.php"><i class="fas fa-globe"></i> Trang Chủ Website</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-box-open"></i>
                    Lịch Sử Nhận Vật Phẩm
                </h1>
            </div>
            
            <!-- Filters -->
            <div class="item-filters">
                <div class="filter-row">
                    <input type="text" id="filterItemName" class="filter-input" placeholder="Tìm theo tên vật phẩm">
                    <select id="filterSource" class="filter-select">
                        <option value="">Tất cả nguồn</option>
                        <option value="lucky_wheel">Vòng Quay May Mắn</option>
                        <option value="accumulated_reward">Phần Thưởng Tích Lũy</option>
                    </select>
                    <input type="date" id="filterStartDate" class="filter-input" placeholder="Từ ngày">
                    <input type="date" id="filterEndDate" class="filter-input" placeholder="Đến ngày">
                    <button class="filter-btn" onclick="loadItems(1)">
                        <i class="fas fa-search"></i> Tìm Kiếm
                    </button>
                    <button class="filter-btn filter-btn-secondary" onclick="resetFilters()">
                        <i class="fas fa-redo"></i> Làm Mới
                    </button>
                </div>
            </div>
            
            <!-- Items Container -->
            <div class="item-container">
                <div id="loadingItems" class="loading-items" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                </div>
                
                <div id="itemsTableWrapper">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Tên vật phẩm</th>
                                <th>Mã vật phẩm</th>
                                <th>Số lượng</th>
                                <th>Nguồn</th>
                                <th>Nhân vật</th>
                                <th>Thời gian nhận</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px; color: #87ceeb;">
                                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="noItems" style="display: none;">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có lịch sử nhận vật phẩm</p>
                </div>
            </div>
            
            <!-- Pagination -->
            <div id="itemPagination" class="item-pagination" style="display: none;">
                <!-- Pagination will be generated here -->
            </div>
        </div>
    </div>
    
    <script>
        let currentPage = 1;
        let totalPages = 1;
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('dashboardSidebar');
            sidebar.classList.toggle('open');
        }
        
        // Load items
        function loadItems(page = 1) {
            currentPage = page;
            
            const loadingEl = document.getElementById('loadingItems');
            const tableBody = document.getElementById('itemsTableBody');
            const noItemsEl = document.getElementById('noItems');
            const paginationEl = document.getElementById('itemPagination');
            
            loadingEl.style.display = 'block';
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #87ceeb;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>';
            noItemsEl.style.display = 'none';
            paginationEl.style.display = 'none';
            
            const params = {
                page: page,
                limit: 20,
                item_name: document.getElementById('filterItemName').value.trim(),
                source: document.getElementById('filterSource').value,
                start_date: document.getElementById('filterStartDate').value,
                end_date: document.getElementById('filterEndDate').value
            };
            
            // Remove empty params
            Object.keys(params).forEach(key => {
                if (params[key] === '' || params[key] === null) {
                    delete params[key];
                }
            });
            
            $.ajax({
                url: '/api/lucky_wheel/get_item_history.php',
                method: 'GET',
                data: params,
                dataType: 'json',
                success: function(response) {
                    loadingEl.style.display = 'none';
                    
                    if (response.success && response.data.items.length > 0) {
                        const items = response.data.items;
                        const pagination = response.data.pagination;
                        
                        tableBody.innerHTML = '';
                        items.forEach(function(item, index) {
                            const rowNum = (pagination.page - 1) * pagination.limit + index + 1;
                            const receivedDate = new Date(item.received_date);
                            const timeStr = receivedDate.toLocaleString('vi-VN', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            const sourceClass = item.source === 'lucky_wheel' ? 'source-lucky-wheel' : 'source-accumulated';
                            const sourceText = item.source === 'lucky_wheel' ? 'Vòng Quay' : 'Tích Lũy';
                            
                            const row = `
                                <tr>
                                    <td>${rowNum}</td>
                                    <td>${escapeHtml(item.item_name)}</td>
                                    <td>${escapeHtml(item.item_code)}</td>
                                    <td>${item.quantity}</td>
                                    <td><span class="source-badge ${sourceClass}">${sourceText}</span></td>
                                    <td>${item.char_name ? escapeHtml(item.char_name) : '-'}</td>
                                    <td>${timeStr}</td>
                                </tr>
                            `;
                            tableBody.innerHTML += row;
                        });
                        
                        // Render pagination
                        renderPagination(pagination);
                        
                    } else {
                        tableBody.innerHTML = '';
                        noItemsEl.style.display = 'block';
                    }
                },
                error: function(xhr) {
                    loadingEl.style.display = 'none';
                    tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #f44336;">Lỗi tải dữ liệu. Vui lòng thử lại.</td></tr>';
                }
            });
        }
        
        // Render pagination
        function renderPagination(pagination) {
            const paginationEl = document.getElementById('itemPagination');
            totalPages = pagination.total_pages;
            
            if (pagination.total_pages <= 1) {
                paginationEl.style.display = 'none';
                return;
            }
            
            paginationEl.style.display = 'flex';
            
            let html = '';
            
            // Previous button
            html += `<button class="pagination-btn" onclick="loadItems(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i> Trước
            </button>`;
            
            // Page info
            html += `<div class="pagination-info">
                Trang 
                <input type="number" class="page-input" value="${pagination.page}" min="1" max="${pagination.total_pages}" 
                       onchange="goToPage(this.value)" onkeypress="if(event.key==='Enter') goToPage(this.value)">
                / ${pagination.total_pages} 
                (Tổng: ${pagination.total.toLocaleString('vi-VN')} mục)
            </div>`;
            
            // Next button
            html += `<button class="pagination-btn" onclick="loadItems(${pagination.page + 1})" ${pagination.page >= pagination.total_pages ? 'disabled' : ''}>
                Sau <i class="fas fa-chevron-right"></i>
            </button>`;
            
            paginationEl.innerHTML = html;
        }
        
        // Go to specific page
        function goToPage(page) {
            page = parseInt(page);
            if (page >= 1 && page <= totalPages) {
                loadItems(page);
            }
        }
        
        // Reset filters
        function resetFilters() {
            document.getElementById('filterItemName').value = '';
            document.getElementById('filterSource').value = '';
            document.getElementById('filterStartDate').value = '';
            document.getElementById('filterEndDate').value = '';
            loadItems(1);
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text || '').replace(/[&<>"']/g, m => map[m]);
        }
        
        // Load items on page load
        $(document).ready(function() {
            loadItems(1);
        });
    </script>
</body>
</html>
