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
                <li><a href="lucky_wheel.php"><i class="fas fa-dharmachakra"></i> Vòng Quay May Mắn</a></li>
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
            
            <!-- Loading -->
            <div id="loading" class="loading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Đang tải...
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <table id="usersTable">
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
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #87ceeb;">
                                Nhấn "Tìm Kiếm" để hiển thị danh sách user
                            </td>
                        </tr>
                    </tbody>
                </table>
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
            <form id="addSilkForm" onsubmit="addSilk(event)">
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
            <form id="changePasswordForm" onsubmit="changePassword(event)">
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
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentPage = 1;
        let currentSearch = '';
        
        // Search users
        function searchUsers(page = 1) {
            currentPage = page;
            currentSearch = $('#search').val().trim();
            
            $('#loading').show();
            $('#usersTableBody').html('<tr><td colspan="7" style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
            
            const params = new URLSearchParams({
                page: page,
                limit: 20
            });
            
            if (currentSearch) {
                params.append('search', currentSearch);
            }
            
            $.ajax({
                url: '/api/cms/users.php?' + params.toString(),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#loading').hide();
                    
                    if (response.success) {
                        displayUsers(response.data);
                        displayPagination(response.pagination);
                    } else {
                        showAlert('error', response.error || 'Có lỗi xảy ra');
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading').hide();
                    showAlert('error', 'Lỗi kết nối: ' + error);
                }
            });
        }
        
        // Display users in table
        function displayUsers(users) {
            const tbody = $('#usersTableBody');
            tbody.empty();
            
            if (users.length === 0) {
                tbody.html('<tr><td colspan="7" style="text-align: center; padding: 20px; color: #87ceeb;">Không tìm thấy user nào</td></tr>');
                return;
            }
            
            users.forEach(function(user) {
                const roleClass = user.role === 'admin' ? 'role-admin' : 'role-user';
                const roleText = user.role === 'admin' ? 'Admin' : 'User';
                const regtime = user.regtime ? new Date(user.regtime).toLocaleString('vi-VN') : '-';
                
                const row = `
                    <tr>
                        <td>${user.jid}</td>
                        <td>${escapeHtml(user.username)}</td>
                        <td>${escapeHtml(user.email || '-')}</td>
                        <td><strong style="color: #e8c088;">${numberFormat(user.silk_own)}</strong></td>
                        <td><span class="role-badge ${roleClass}">${roleText}</span></td>
                        <td>${regtime}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="openAddSilkModal(${user.jid}, '${escapeHtml(user.username)}', ${user.silk_own})">
                                    <i class="fas fa-coins"></i> Cộng Silk
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="openChangePasswordModal('${escapeHtml(user.username)}', '${escapeHtml(user.email || '')}')">
                                    <i class="fas fa-key"></i> Đổi MK
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        
        // Display pagination
        function displayPagination(pagination) {
            const paginationDiv = $('#pagination');
            
            if (pagination.total_pages <= 1) {
                paginationDiv.hide();
                return;
            }
            
            paginationDiv.show();
            paginationDiv.empty();
            
            // Previous button
            paginationDiv.append(`
                <button onclick="searchUsers(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i> Trước
                </button>
            `);
            
            // Page info
            paginationDiv.append(`
                <span class="pagination-info">
                    Trang ${pagination.page} / ${pagination.total_pages} (Tổng: ${numberFormat(pagination.total)} user)
                </span>
            `);
            
            // Next button
            paginationDiv.append(`
                <button onclick="searchUsers(${pagination.page + 1})" ${pagination.page >= pagination.total_pages ? 'disabled' : ''}>
                    Sau <i class="fas fa-chevron-right"></i>
                </button>
            `);
        }
        
        // Open Add Silk Modal
        function openAddSilkModal(jid, username, currentSilk) {
            $('#addSilkJID').val(jid);
            $('#addSilkUsername').val(username);
            $('#addSilkCurrent').val(numberFormat(currentSilk));
            $('#addSilkAmount').val('');
            $('#addSilkModal').show();
        }
        
        // Add Silk
        function addSilk(event) {
            event.preventDefault();
            
            const jid = parseInt($('#addSilkJID').val());
            const amount = parseInt($('#addSilkAmount').val());
            
            if (amount <= 0 || amount > 10000000) {
                showAlert('error', 'Số lượng silk không hợp lệ (1 - 10,000,000)');
                return;
            }
            
            if (!confirm(`Xác nhận cộng ${numberFormat(amount)} silk cho user ${$('#addSilkUsername').val()}?`)) {
                return;
            }
            
            $.ajax({
                url: '/api/cms/add_silk.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ jid: jid, amount: amount }),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        closeModal('addSilkModal');
                        searchUsers(currentPage); // Refresh list
                    } else {
                        showAlert('error', response.error || 'Có lỗi xảy ra');
                    }
                },
                error: function(xhr, status, error) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Lỗi kết nối: ' + error);
                }
            });
        }
        
        // Open Change Password Modal
        function openChangePasswordModal(username, email) {
            $('#changePasswordUsername').val(username);
            $('#changePasswordEmail').val(email);
            $('#changePasswordNew').val('');
            $('#changePasswordConfirm').val('');
            $('#changePasswordModal').show();
        }
        
        // Change Password
        function changePassword(event) {
            event.preventDefault();
            
            const email = $('#changePasswordEmail').val().trim();
            const newPassword = $('#changePasswordNew').val();
            const confirmPassword = $('#changePasswordConfirm').val();
            
            if (newPassword !== confirmPassword) {
                showAlert('error', 'Mật khẩu xác nhận không khớp');
                return;
            }
            
            if (newPassword.length < 6) {
                showAlert('error', 'Mật khẩu phải có ít nhất 6 ký tự');
                return;
            }
            
            if (!confirm(`Xác nhận đổi mật khẩu cho user ${$('#changePasswordUsername').val()}?`)) {
                return;
            }
            
            $.ajax({
                url: '/api/cms/change_password.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    email: email,
                    new_password: newPassword
                }),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        closeModal('changePasswordModal');
                    } else {
                        showAlert('error', response.error || 'Có lỗi xảy ra');
                    }
                },
                error: function(xhr, status, error) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.error || 'Lỗi kết nối: ' + error);
                }
            });
        }
        
        // Close Modal
        function closeModal(modalId) {
            $('#' + modalId).hide();
        }
        
        // Reset Search
        function resetSearch() {
            $('#search').val('');
            searchUsers(1);
        }
        
        // Show Alert
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
        
        function numberFormat(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Enter key to search
        $('#search').on('keypress', function(e) {
            if (e.which === 13) {
                searchUsers(1);
            }
        });
    </script>
</body>
</html>
