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
    <title>Lịch Sử Giao Dịch - Song Long Tranh Bá Mobile</title>
    
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
            flex: 1;
            margin-left: 260px;
            width: calc(100% - 260px);
            max-width: calc(100% - 260px);
            background: rgba(10, 20, 40, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            position: relative;
            overflow-x: hidden;
            overflow-y: visible;
            min-height: 100vh;
            box-sizing: border-box;
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1e90ff;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #ffd700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(30, 144, 255, 0.2);
            border: 1px solid rgba(30, 144, 255, 0.4);
            color: #87ceeb;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(30, 144, 255, 0.3);
            transform: translateY(-2px);
        }
        
        /* Transaction Filters */
        .transaction-filters {
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
        
        /* Transaction Container */
        .transaction-container {
            background: rgba(20, 30, 50, 0.8);
            border: 2px solid #4682b4;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }
        
        .loading-transactions {
            text-align: center;
            padding: 40px;
            color: #87ceeb;
            font-size: 16px;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            color: #87ceeb;
        }
        
        .transaction-table thead {
            background: rgba(30, 144, 255, 0.1);
        }
        
        .transaction-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #1e90ff;
            border-bottom: 2px solid #1e90ff;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .transaction-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }
        
        .transaction-table tbody tr:hover {
            background: rgba(30, 144, 255, 0.05);
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
        
        .transaction-pagination {
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
        
        #noTransactions {
            text-align: center;
            padding: 40px;
            color: #87ceeb;
        }
        
        #noTransactions i {
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
            
            .transaction-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .transaction-table th,
            .transaction-table td {
                padding: 8px 5px;
                white-space: nowrap;
            }
            
            .transaction-container {
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Trang Chủ</a></li>
                <li><a href="transaction_history.php" class="active"><i class="fas fa-history"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="payment.php"><i class="fas fa-credit-card"></i> Nạp Tiền</a></li>
                <li><a href="download.php"><i class="fas fa-download"></i> Tải Game</a></li>
                <li><a href="ranking.php"><i class="fas fa-trophy"></i> Xếp Hạng</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin/cms/index.php"><i class="fas fa-cog"></i> CMS Admin</a></li>
                <?php endif; ?>
                <li><a href="index.php"><i class="fas fa-globe"></i> Trang Chủ Website</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <div class="page-container">
            <!-- Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-history"></i>
                    Lịch Sử Giao Dịch
                </h1>
            </div>
            
            <!-- Filters -->
            <div class="transaction-filters">
                <div class="filter-row">
                    <input 
                        type="text" 
                        id="orderCodeFilter" 
                        placeholder="Tìm theo Order Code..."
                        class="filter-input"
                    >
                    <select id="statusFilter" class="filter-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">Đang chờ</option>
                        <option value="processing">Đang xử lý</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="failed">Thất bại</option>
                        <option value="expired">Hết hạn</option>
                    </select>
                    <button onclick="loadTransactions()" class="filter-btn">
                        <i class="fas fa-search"></i> Tìm Kiếm
                    </button>
                    <button onclick="resetTransactionFilters()" class="filter-btn filter-btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
            
            <!-- Transaction Table -->
            <div class="transaction-container">
                <div class="loading-transactions" id="loadingTransactions" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                </div>
                <div class="transaction-table-wrapper">
                    <table class="transaction-table" id="transactionTable" style="display: none;">
                        <thead>
                            <tr>
                                <th>Order Code</th>
                                <th>Số Tiền</th>
                                <th>Silk</th>
                                <th>Trạng Thái</th>
                                <th>Phương Thức</th>
                                <th>Ngày Tạo</th>
                                <th>Ngày Hoàn Thành</th>
                            </tr>
                        </thead>
                        <tbody id="transactionBody">
                        </tbody>
                    </table>
                    <div id="noTransactions" style="display: none;">
                        <i class="fas fa-inbox"></i>
                        <p>Không có giao dịch nào</p>
                    </div>
                </div>
                <div class="transaction-pagination" id="transactionPagination" style="display: none;">
                    <button onclick="changeTransactionPage(-1)" id="prevTransactionBtn" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Trước
                    </button>
                    <span class="pagination-info">
                        Trang <input type="number" id="transactionPageInput" min="1" value="1" class="page-input"> / <span id="totalTransactionPages">1</span>
                    </span>
                    <button onclick="changeTransactionPage(1)" id="nextTransactionBtn" class="pagination-btn">
                        Sau <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Transaction History
        let currentTransactionPage = 1;
        let totalTransactionPages = 1;
        
        // Load transactions on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactions();
            
            // Allow Enter key in filter inputs
            const orderCodeFilter = document.getElementById('orderCodeFilter');
            if (orderCodeFilter) {
                orderCodeFilter.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        loadTransactions();
                    }
                });
            }
            
            // Allow Enter key in page input
            const transactionPageInput = document.getElementById('transactionPageInput');
            if (transactionPageInput) {
                transactionPageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const page = parseInt(this.value);
                        if (page >= 1 && page <= totalTransactionPages) {
                            currentTransactionPage = page;
                            loadTransactions();
                        }
                    }
                });
            }
        });
        
        // Load transactions
        async function loadTransactions(page = 1) {
            const loading = document.getElementById('loadingTransactions');
            const table = document.getElementById('transactionTable');
            const noData = document.getElementById('noTransactions');
            const pagination = document.getElementById('transactionPagination');
            
            if (!loading || !table || !noData || !pagination) {
                console.error('Transaction elements not found');
                return;
            }
            
            loading.style.display = 'block';
            table.style.display = 'none';
            noData.style.display = 'none';
            pagination.style.display = 'none';
            
            const params = new URLSearchParams({
                page: page,
                limit: 10
            });
            
            const orderCodeFilter = document.getElementById('orderCodeFilter');
            const statusFilter = document.getElementById('statusFilter');
            
            const orderCode = orderCodeFilter ? orderCodeFilter.value.trim() : '';
            const status = statusFilter ? statusFilter.value : '';
            
            if (orderCode) params.append('order_code', orderCode);
            if (status) params.append('status', status);
            
            try {
                const response = await fetch(`api/sepay/get_user_orders.php?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    const orders = result.orders || [];
                    totalTransactionPages = result.pagination?.total_pages || 1;
                    currentTransactionPage = result.pagination?.page || 1;
                    
                    if (orders.length > 0) {
                        displayTransactions(orders);
                        table.style.display = 'table';
                        updateTransactionPagination();
                        pagination.style.display = 'flex';
                    } else {
                        noData.style.display = 'block';
                        pagination.style.display = 'flex';
                        updateTransactionPagination();
                    }
                } else {
                    console.error('Error loading transactions:', result.error);
                    noData.style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
                noData.style.display = 'block';
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Display transactions
        function displayTransactions(orders) {
            const tbody = document.getElementById('transactionBody');
            if (!tbody) return;
            
            tbody.innerHTML = orders.map(order => {
                const statusClass = 'status-' + (order.Status || 'pending');
                const statusText = getStatusText(order.Status);
                const amount = parseFloat(order.Amount || 0).toLocaleString('vi-VN');
                const silk = parseInt(order.SilkAmount || 0).toLocaleString('vi-VN');
                const createdDate = formatDate(order.CreatedDate);
                const completedDate = order.CompletedDate ? formatDate(order.CompletedDate) : '-';
                const paymentMethod = getPaymentMethodText(order.PaymentMethod);
                
                return `
                    <tr>
                        <td><strong>${escapeHtml(order.OrderCode || '')}</strong></td>
                        <td>${amount} VNĐ</td>
                        <td>${silk} Silk</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${paymentMethod}</td>
                        <td>${createdDate}</td>
                        <td>${completedDate}</td>
                    </tr>
                `;
            }).join('');
        }
        
        // Get status text
        function getStatusText(status) {
            const statusMap = {
                'pending': 'Đang chờ',
                'processing': 'Đang xử lý',
                'completed': 'Hoàn thành',
                'failed': 'Thất bại',
                'expired': 'Hết hạn'
            };
            return statusMap[status] || status || 'Đang chờ';
        }
        
        // Get payment method text
        function getPaymentMethodText(method) {
            const methodMap = {
                'QR_CODE': 'QR Code',
                'BANK_TRANSFER': 'Chuyển khoản',
                'CARD': 'Thẻ cào'
            };
            return methodMap[method] || method || '-';
        }
        
        // Format date
        function formatDate(dateString) {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                return date.toLocaleString('vi-VN', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Update pagination
        function updateTransactionPagination() {
            const pageInput = document.getElementById('transactionPageInput');
            const totalPagesSpan = document.getElementById('totalTransactionPages');
            const prevBtn = document.getElementById('prevTransactionBtn');
            const nextBtn = document.getElementById('nextTransactionBtn');
            
            if (pageInput) pageInput.value = currentTransactionPage;
            if (totalPagesSpan) totalPagesSpan.textContent = totalTransactionPages;
            if (prevBtn) prevBtn.disabled = currentTransactionPage <= 1;
            if (nextBtn) nextBtn.disabled = currentTransactionPage >= totalTransactionPages;
        }
        
        // Change page
        function changeTransactionPage(direction) {
            const newPage = currentTransactionPage + direction;
            if (newPage >= 1 && newPage <= totalTransactionPages) {
                currentTransactionPage = newPage;
                loadTransactions(newPage);
            }
        }
        
        // Reset filters
        function resetTransactionFilters() {
            const orderCodeFilter = document.getElementById('orderCodeFilter');
            const statusFilter = document.getElementById('statusFilter');
            
            if (orderCodeFilter) orderCodeFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            currentTransactionPage = 1;
            loadTransactions(1);
        }
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('dashboardSidebar');
            sidebar.classList.toggle('open');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('dashboardSidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (sidebar && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>



