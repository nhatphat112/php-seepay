<?php
/**
 * CMS Admin - User Management
 * Quản lý users: xem danh sách, tìm kiếm, cộng silk, đổi mật khẩu
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý User - CMS</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
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
            max-width: 500px;
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
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .role-admin {
            background: #ff6b6b;
            color: #fff;
        }
        
        .role-user {
            background: #4682b4;
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
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Quản Lý User</a></li>
                <li><a href="tichnap/index.php"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-users"></i> Quản Lý User</h2>
                <p>Xem danh sách user, tìm kiếm, cộng silk và đổi mật khẩu</p>
            </div>
            
            <!-- Search Filters -->
            <div class="filters-container">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="search">Tìm kiếm (Username / Email)</label>
                        <input type="text" id="search" placeholder="Nhập username hoặc email...">
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="searchUsers()">
                        <i class="fas fa-search"></i> Tìm Kiếm
                    </button>
                    <button class="btn btn-secondary" onclick="resetSearch()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertMessage" class="alert" style="display: none;"></div>
            
            <!-- Users Table -->
            <div class="table-container">
                <div class="loading" id="loading">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                </div>
                <table id="usersTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>JID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Silk</th>
                            <th>Role</th>
                            <th>Ngày đăng ký</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                    </tbody>
                </table>
                <div id="noData" style="display: none; text-align: center; padding: 40px; color: #87ceeb;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p>Không tìm thấy user nào</p>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="pagination" id="pagination" style="display: none;"></div>
        </main>
    </div>
    
    <!-- Modal: Add Silk -->
    <div id="addSilkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-coins"></i> Cộng Silk</h3>
                <span class="close" onclick="closeModal('addSilkModal')">&times;</span>
            </div>
            <form id="addSilkForm" onsubmit="addSilk(event); return false;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="addSilkUsername" readonly style="background: #0f1624; color: #87ceeb;">
                </div>
                <div class="form-group">
                    <label>Silk hiện tại</label>
                    <input type="text" id="addSilkCurrent" readonly style="background: #0f1624; color: #87ceeb;">
                </div>
                <div class="form-group">
                    <label for="addSilkAmount">Số lượng Silk cộng thêm</label>
                    <input type="number" id="addSilkAmount" min="1" max="10000000" required>
                    <small>Số lượng silk muốn cộng thêm (1 - 10,000,000)</small>
                </div>
                <input type="hidden" id="addSilkJID">
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Xác Nhận
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addSilkModal')">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Change Password -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Đổi Mật Khẩu</h3>
                <span class="close" onclick="closeModal('changePasswordModal')">&times;</span>
            </div>
            <form id="changePasswordForm" onsubmit="changePassword(event); return false;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="changePasswordUsername" readonly style="background: #0f1624; color: #87ceeb;">
                </div>
                <div class="form-group">
                    <label for="changePasswordEmail">Email</label>
                    <input type="email" id="changePasswordEmail" required>
                    <small>Nhập email của user để xác nhận</small>
                </div>
                <div class="form-group">
                    <label for="changePasswordNew">Mật khẩu mới</label>
                    <input type="password" id="changePasswordNew" minlength="6" required>
                    <small>Mật khẩu mới (tối thiểu 6 ký tự)</small>
                </div>
                <div class="form-group">
                    <label for="changePasswordConfirm">Xác nhận mật khẩu</label>
                    <input type="password" id="changePasswordConfirm" minlength="6" required>
                    <small>Nhập lại mật khẩu mới</small>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Xác Nhận
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('changePasswordModal')">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let currentPage = 1;
        let totalPages = 1;
        
        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Allow Enter key to search
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchUsers();
                    }
                });
            }
        });
        
        // Load users
        async function loadUsers(page = 1) {
            const loading = document.getElementById('loading');
            const table = document.getElementById('usersTable');
            const tbody = document.getElementById('usersTableBody');
            const pagination = document.getElementById('pagination');
            const noData = document.getElementById('noData');
            
            loading.style.display = 'block';
            table.style.display = 'none';
            noData.style.display = 'none';
            pagination.style.display = 'none';
            
            const params = new URLSearchParams({
                page: page,
                limit: 20
            });
            
            const search = document.getElementById('search').value.trim();
            if (search) {
                params.append('search', search);
            }
            
            try {
                const response = await fetch(`../api/cms/users.php?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    const users = result.data;
                    totalPages = result.pagination.total_pages;
                    currentPage = result.pagination.page;
                    
                    // Store result for summary
                    window.lastResult = result;
                    
                    if (users.length > 0) {
                        displayUsers(users);
                        table.style.display = 'table';
                        noData.style.display = 'none';
                        updatePagination();
                    } else {
                        table.style.display = 'none';
                        noData.style.display = 'block';
                        updatePagination();
                    }
                } else {
                    alert('Lỗi: ' + (result.error || result.message || 'Có lỗi xảy ra'));
                    table.style.display = 'none';
                    noData.style.display = 'block';
                    noData.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 15px; color: #ff6b6b;"></i><p>' + (result.error || result.message || 'Có lỗi xảy ra') + '</p>';
                }
            } catch (error) {
                alert('Lỗi kết nối: ' + error.message);
                table.style.display = 'none';
                noData.style.display = 'block';
                noData.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 15px; color: #ff6b6b;"></i><p>Lỗi kết nối: ' + error.message + '</p>';
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Search users
        function searchUsers() {
            loadUsers(1);
        }
        
        // Display users in table
        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = users.map(user => {
                const roleClass = user.role === 'admin' ? 'role-admin' : 'role-user';
                const roleText = user.role === 'admin' ? 'Admin' : 'User';
                const regtime = user.regtime ? formatDate(user.regtime) : '-';
                const username = escapeHtml(user.username);
                const email = escapeHtml(user.email || '-');
                
                return `
                    <tr>
                        <td>${user.jid}</td>
                        <td>${username}</td>
                        <td>${email}</td>
                        <td><strong style="color: #e8c088;">${numberFormat(user.silk_own)}</strong></td>
                        <td><span class="role-badge ${roleClass}">${roleText}</span></td>
                        <td>${regtime}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="openAddSilkModal(${user.jid}, '${username}', ${user.silk_own})">
                                    <i class="fas fa-coins"></i> Cộng Silk
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="openChangePasswordModal('${username}', '${email}')">
                                    <i class="fas fa-key"></i> Đổi MK
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // Update pagination
        function updatePagination() {
            const pagination = document.getElementById('pagination');
            
            if (totalPages <= 1) {
                pagination.style.display = 'none';
                return;
            }
            
            pagination.style.display = 'flex';
            pagination.innerHTML = `
                <button onclick="changePage(-1)" id="prevBtn" ${currentPage <= 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i> Trước
                </button>
                <span class="pagination-info">
                    Trang ${currentPage} / ${totalPages} (Tổng: ${numberFormat(window.lastResult?.pagination?.total || 0)} user)
                </span>
                <button onclick="changePage(1)" id="nextBtn" ${currentPage >= totalPages ? 'disabled' : ''}>
                    Sau <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }
        
        // Change page
        function changePage(delta) {
            const newPage = currentPage + delta;
            if (newPage >= 1 && newPage <= totalPages) {
                loadUsers(newPage);
            }
        }
        
        // Open Add Silk Modal
        function openAddSilkModal(jid, username, currentSilk) {
            document.getElementById('addSilkJID').value = jid;
            document.getElementById('addSilkUsername').value = username;
            document.getElementById('addSilkCurrent').value = numberFormat(currentSilk);
            document.getElementById('addSilkAmount').value = '';
            document.getElementById('addSilkModal').style.display = 'block';
        }
        
        // Add Silk
        async function addSilk(event) {
            event.preventDefault();
            
            const jid = parseInt(document.getElementById('addSilkJID').value);
            const amount = parseInt(document.getElementById('addSilkAmount').value);
            const username = document.getElementById('addSilkUsername').value;
            
            if (amount <= 0 || amount > 10000000) {
                showAlert('error', 'Số lượng silk không hợp lệ (1 - 10,000,000)');
                return;
            }
            
            if (!confirm(`Xác nhận cộng ${numberFormat(amount)} silk cho user ${username}?`)) {
                return;
            }
            
            try {
                const response = await fetch('../api/cms/add_silk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ jid: jid, amount: amount })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    closeModal('addSilkModal');
                    loadUsers(currentPage); // Refresh list
                } else {
                    showAlert('error', result.error || 'Có lỗi xảy ra');
                }
            } catch (error) {
                showAlert('error', 'Lỗi kết nối: ' + error.message);
            }
        }
        
        // Open Change Password Modal
        function openChangePasswordModal(username, email) {
            document.getElementById('changePasswordUsername').value = username;
            document.getElementById('changePasswordEmail').value = email;
            document.getElementById('changePasswordNew').value = '';
            document.getElementById('changePasswordConfirm').value = '';
            document.getElementById('changePasswordModal').style.display = 'block';
        }
        
        // Change Password
        async function changePassword(event) {
            event.preventDefault();
            
            const email = document.getElementById('changePasswordEmail').value.trim();
            const newPassword = document.getElementById('changePasswordNew').value;
            const confirmPassword = document.getElementById('changePasswordConfirm').value;
            const username = document.getElementById('changePasswordUsername').value;
            
            if (newPassword !== confirmPassword) {
                showAlert('error', 'Mật khẩu xác nhận không khớp');
                return;
            }
            
            if (newPassword.length < 6) {
                showAlert('error', 'Mật khẩu phải có ít nhất 6 ký tự');
                return;
            }
            
            if (!confirm(`Xác nhận đổi mật khẩu cho user ${username}?`)) {
                return;
            }
            
            try {
                const response = await fetch('../api/cms/change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: email,
                        new_password: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    closeModal('changePasswordModal');
                } else {
                    showAlert('error', result.error || 'Có lỗi xảy ra');
                }
            } catch (error) {
                showAlert('error', 'Lỗi kết nối: ' + error.message);
            }
        }
        
        // Close Modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Reset Search
        function resetSearch() {
            document.getElementById('search').value = '';
            loadUsers(1);
        }
        
        // Show Alert
        function showAlert(type, message) {
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.className = 'alert alert-' + type;
            alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + escapeHtml(message);
            alertDiv.style.display = 'block';
            
            setTimeout(function() {
                alertDiv.style.display = 'none';
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
        
        function numberFormat(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN');
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
