<?php
/**
 * CMS Admin - Orders Management
 * Xem và tìm kiếm lịch sử giao dịch
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Giao Dịch - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    <style>
        .filters-container {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            margin-bottom: 20px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination button {
            padding: 8px 15px;
            background: #16213e;
            border: 1px solid #4682b4;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #4682b4;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-info {
            color: #87ceeb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #ffa500;
            color: #1a1a2e;
        }
        
        .status-processing {
            background: #1e90ff;
            color: #fff;
        }
        
        .status-completed {
            background: #4caf50;
            color: #fff;
        }
        
        .status-failed {
            background: #f44336;
            color: #fff;
        }
        
        .status-expired {
            background: #9e9e9e;
            color: #fff;
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
                <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="tichnap/index.php"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</h2>
                <p>Tìm kiếm và xem lịch sử giao dịch</p>
            </div>
            
            <div class="filters-container">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="orderCode">Order Code</label>
                        <input type="text" id="orderCode" placeholder="Tìm theo order code...">
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" placeholder="Tìm theo username...">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status">
                            <option value="">Tất cả</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="searchOrders()">
                        <i class="fas fa-search"></i> Tìm Kiếm
                    </button>
                    <button class="btn btn-secondary" onclick="resetFilters()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <div class="loading" id="loading">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                </div>
                <table id="ordersTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Username</th>
                            <th>Amount</th>
                            <th>Silk</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Created Date</th>
                            <th>Completed Date</th>
                        </tr>
                    </thead>
                    <tbody id="ordersBody">
                    </tbody>
                </table>
                <div id="noData" style="display: none; text-align: center; padding: 40px; color: #87ceeb;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p>Không tìm thấy orders nào</p>
                </div>
            </div>
            
            <div class="pagination" id="pagination" style="display: none;">
                <button onclick="changePage(-1)" id="prevBtn" class="btn btn-secondary btn-sm">
                    <i class="fas fa-chevron-left"></i> Trước
                </button>
                <span class="pagination-info" id="pageInfo"></span>
                <input type="number" id="pageInput" min="1" value="1" style="width: 60px; padding: 5px; margin: 0 10px; background: #0f1624; border: 1px solid #4682b4; border-radius: 5px; color: #fff; text-align: center;">
                <span style="color: #87ceeb; margin-right: 10px;">/ <span id="totalPagesSpan">1</span></span>
                <button onclick="goToPage()" class="btn btn-secondary btn-sm" style="margin-right: 10px;">
                    <i class="fas fa-arrow-right"></i> Đi
                </button>
                <button onclick="changePage(1)" id="nextBtn" class="btn btn-secondary btn-sm">
                    Sau <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div id="paginationSummary" style="text-align: center; margin-top: 15px; color: #87ceeb; display: none;">
                <span id="summaryText"></span>
            </div>
        </main>
    </div>
    
    <script>
        let currentPage = 1;
        let totalPages = 1;
        
        // Load orders on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadOrders();
            
            // Allow Enter key to go to page
            const pageInput = document.getElementById('pageInput');
            if (pageInput) {
                pageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        goToPage();
                    }
                });
            }
        });
        
        // Load orders
        async function loadOrders(page = 1) {
            const loading = document.getElementById('loading');
            const table = document.getElementById('ordersTable');
            const noData = document.getElementById('noData');
            const pagination = document.getElementById('pagination');
            
            loading.style.display = 'block';
            table.style.display = 'none';
            noData.style.display = 'none';
            pagination.style.display = 'none';
            
            const params = new URLSearchParams({
                page: page,
                limit: 20
            });
            
            const orderCode = document.getElementById('orderCode').value.trim();
            const username = document.getElementById('username').value.trim();
            const status = document.getElementById('status').value;
            
            if (orderCode) params.append('order_code', orderCode);
            if (username) params.append('username', username);
            if (status) params.append('status', status);
            
            try {
                const response = await fetch(`../api/cms/orders.php?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    const orders = result.data;
                    totalPages = result.pagination.total_pages;
                    currentPage = result.pagination.page;
                    
                    // Store result for summary
                    window.lastResult = result;
                    
                    if (orders.length > 0) {
                        displayOrders(orders);
                        table.style.display = 'table';
                        updatePagination();
                    } else {
                        noData.style.display = 'block';
                        updatePagination(); // Still update to show summary
                    }
                } else {
                    alert('Lỗi: ' + result.error);
                }
            } catch (error) {
                alert('Lỗi kết nối: ' + error.message);
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Display orders
        function displayOrders(orders) {
            const tbody = document.getElementById('ordersBody');
            tbody.innerHTML = orders.map(order => `
                <tr>
                    <td>${order.order_code}</td>
                    <td>${order.username || '-'}</td>
                    <td>${formatCurrency(order.amount)}</td>
                    <td>${order.silk_amount}</td>
                    <td><span class="status-badge status-${order.status}">${order.status}</span></td>
                    <td>${order.payment_method || '-'}</td>
                    <td>${formatDate(order.created_date)}</td>
                    <td>${order.completed_date ? formatDate(order.completed_date) : '-'}</td>
                </tr>
            `).join('');
        }
        
        // Update pagination
        function updatePagination() {
            const pagination = document.getElementById('pagination');
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInput = document.getElementById('pageInput');
            const totalPagesSpan = document.getElementById('totalPagesSpan');
            const summary = document.getElementById('paginationSummary');
            const summaryText = document.getElementById('summaryText');
            
            // Always show pagination if there are any results
            if (totalPages > 0) {
                pagination.style.display = 'flex';
                summary.style.display = 'block';
                
                pageInfo.textContent = `Trang ${currentPage} / ${totalPages}`;
                pageInput.value = currentPage;
                pageInput.max = totalPages;
                totalPagesSpan.textContent = totalPages;
                
                prevBtn.disabled = currentPage === 1;
                nextBtn.disabled = currentPage === totalPages;
                
                // Update summary
                const totalRecords = window.lastResult?.pagination?.total || 0;
                const startRecord = totalRecords > 0 ? ((currentPage - 1) * 20) + 1 : 0;
                const endRecord = Math.min(currentPage * 20, totalRecords);
                summaryText.textContent = `Hiển thị ${startRecord}-${endRecord} trong tổng số ${totalRecords} giao dịch`;
            } else {
                pagination.style.display = 'none';
                summary.style.display = 'none';
            }
        }
        
        // Go to specific page
        function goToPage() {
            const pageInput = document.getElementById('pageInput');
            const page = parseInt(pageInput.value);
            if (page >= 1 && page <= totalPages) {
                loadOrders(page);
            } else {
                alert(`Vui lòng nhập số trang từ 1 đến ${totalPages}`);
            }
        }
        
        
        // Change page
        function changePage(delta) {
            const newPage = currentPage + delta;
            if (newPage >= 1 && newPage <= totalPages) {
                loadOrders(newPage);
            }
        }
        
        // Search orders
        function searchOrders() {
            loadOrders(1);
        }
        
        // Reset filters
        function resetFilters() {
            document.getElementById('orderCode').value = '';
            document.getElementById('username').value = '';
            document.getElementById('status').value = '';
            loadOrders(1);
        }
        
        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }
        
        // Format date
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN');
        }
    </script>
</body>
</html>

