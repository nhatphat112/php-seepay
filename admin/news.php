<?php
/**
 * CMS Admin - News Management
 * Quản lý tin bài (copy link từ FB)
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin Bài - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    <style>
        .news-item {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            margin-bottom: 15px;
        }
        
        .news-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #0f1624;
        }
        
        .news-title {
            font-size: 1.1rem;
            color: #e8c088;
            font-weight: 600;
        }
        
        .news-meta {
            display: flex;
            gap: 15px;
            color: #87ceeb;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .news-link {
            color: #87ceeb;
            word-break: break-all;
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
                <li><a href="news.php" class="active"><i class="fas fa-newspaper"></i> Tin Bài</a></li>
                <li><a href="social.php"><i class="fas fa-share-alt"></i> Social Links</a></li>
                <li><a href="server_info.php"><i class="fas fa-server"></i> Thông Tin Server</a></li>
                <li><a href="weekly_events.php"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Quản Lý User</a></li>
                <li><a href="lucky_wheel.php"><i class="fas fa-dharmachakra"></i> Vòng Quay May Mắn</a></li>
                <li><a href="tichnap/index.php"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-newspaper"></i> Tin Bài</h2>
                <p>Quản lý tin tức sự kiện, copy link từ FB</p>
            </div>
            
            <div class="alert alert-success" id="alertSuccess" style="display: none;"></div>
            <div class="alert alert-error" id="alertError" style="display: none;"></div>
            
            <button class="btn btn-primary" onclick="showAddForm()" style="margin-bottom: 20px;">
                <i class="fas fa-plus"></i> Thêm Tin Bài Mới
            </button>
            
            <div id="newsList"></div>
            <div class="loading" id="loading">Đang tải...</div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="newsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
        <div style="max-width: 600px; margin: 50px auto; background: #16213e; padding: 30px; border-radius: 10px;">
            <h3 style="color: #e8c088; margin-bottom: 20px;" id="modalTitle">Thêm Tin Bài</h3>
            <form id="newsForm">
                <input type="hidden" id="newsId" name="news_id">
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Chọn category</option>
                        <option value="Tin Nóng">Tin Nóng</option>
                        <option value="Sự Kiện">Sự Kiện</option>
                        <option value="Cập Nhật">Cập Nhật</option>
                        <option value="Tin Tức">Tin Tức</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required placeholder="Nhập tiêu đề tin bài">
                </div>
                
                <div class="form-group">
                    <label for="linkUrl">Link URL (Copy từ FB) *</label>
                    <input type="url" id="linkUrl" name="link_url" required placeholder="https://www.facebook.com/...">
                    <small>Copy link từ Facebook và paste vào đây</small>
                </div>
                
                <div class="form-group">
                    <label for="displayOrder">Display Order</label>
                    <input type="number" id="displayOrder" name="display_order" value="0" min="0">
                    <small>Số nhỏ hơn sẽ hiển thị trước</small>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let editingId = null;
        
        // Load news on page load
        document.addEventListener('DOMContentLoaded', loadNews);
        
        async function loadNews() {
            document.getElementById('loading').style.display = 'block';
            try {
                const res = await fetch('../api/cms/news.php');
                const data = await res.json();
                if (data.success) {
                    displayNews(data.data);
                } else {
                    showError('Lỗi: ' + data.error);
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
        
        function displayNews(newsList) {
            const list = document.getElementById('newsList');
            if (newsList.length === 0) {
                list.innerHTML = '<div style="text-align: center; padding: 40px; color: #87ceeb;">Chưa có tin bài nào</div>';
                return;
            }
            
            list.innerHTML = newsList.map(n => `
                <div class="news-item">
                    <div class="news-item-header">
                        <div>
                            <div class="news-title">${escapeHtml(n.title)}</div>
                            <div class="news-meta">
                                <span><i class="fas fa-tag"></i> ${escapeHtml(n.category)}</span>
                                <span><i class="fas fa-sort-numeric-up"></i> Order: ${n.display_order}</span>
                                <span><i class="fas fa-calendar"></i> ${formatDate(n.created_date)}</span>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm" onclick="editNews(${n.news_id})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteNews(${n.news_id})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                    <div class="news-link">
                        <i class="fas fa-link"></i> 
                        <a href="${escapeHtml(n.link_url)}" target="_blank" style="color: #87ceeb;">
                            ${escapeHtml(n.link_url)}
                        </a>
                    </div>
                </div>
            `).join('');
        }
        
        function showAddForm() {
            editingId = null;
            document.getElementById('modalTitle').textContent = 'Thêm Tin Bài';
            document.getElementById('newsForm').reset();
            document.getElementById('newsId').value = '';
            document.getElementById('newsModal').style.display = 'block';
        }
        
        function editNews(id) {
            editingId = id;
            document.getElementById('modalTitle').textContent = 'Sửa Tin Bài';
            
            // Load news data
            fetch(`../api/cms/news.php?news_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        const n = data.data;
                        document.getElementById('newsId').value = n.news_id;
                        document.getElementById('category').value = n.category;
                        document.getElementById('title').value = n.title;
                        document.getElementById('linkUrl').value = n.link_url;
                        document.getElementById('displayOrder').value = n.display_order || 0;
                        document.getElementById('newsModal').style.display = 'block';
                    } else {
                        showError('Không tìm thấy tin bài');
                    }
                })
                .catch(e => showError('Lỗi: ' + e.message));
        }
        
        function closeModal() {
            document.getElementById('newsModal').style.display = 'none';
        }
        
        // Handle form submit
        document.getElementById('newsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                category: document.getElementById('category').value,
                title: document.getElementById('title').value.trim(),
                link_url: document.getElementById('linkUrl').value.trim(),
                display_order: parseInt(document.getElementById('displayOrder').value) || 0
            };
            
            if (editingId) {
                formData.news_id = editingId;
            }
            
            try {
                const res = await fetch('../api/cms/news.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const result = await res.json();
                if (result.success) {
                    showSuccess(editingId ? 'Đã cập nhật tin bài!' : 'Đã thêm tin bài!');
                    closeModal();
                    loadNews();
                } else {
                    showError('Lỗi: ' + result.error);
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            }
        });
        
        async function deleteNews(id) {
            if (!confirm('Xóa tin bài này?')) return;
            
            try {
                const res = await fetch(`../api/cms/news.php?news_id=${id}`, {
                    method: 'DELETE'
                });
                const result = await res.json();
                if (result.success) {
                    showSuccess('Đã xóa tin bài!');
                    loadNews();
                } else {
                    showError('Lỗi: ' + result.error);
                }
            } catch (e) {
                showError('Lỗi: ' + e.message);
            }
        }
        
        function showSuccess(msg) {
            const el = document.getElementById('alertSuccess');
            el.textContent = msg;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 3000);
        }
        
        function showError(msg) {
            const el = document.getElementById('alertError');
            el.textContent = msg;
            el.style.display = 'block';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }
        
        // Close modal on outside click
        document.getElementById('newsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

