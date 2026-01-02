<?php
/**
 * CMS Admin - Weekly Events Management
 * Quản lý 5 sự kiện trong tuần
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sự Kiện Trong Tuần - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    <style>
        .event-item {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            margin-bottom: 15px;
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .event-title {
            font-size: 1.1rem;
            color: #e8c088;
            font-weight: 600;
        }
        
        .event-meta {
            color: #87ceeb;
            font-size: 0.9rem;
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
                <li><a href="weekly_events.php" class="active"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</h2>
                <p>Quản lý 5 sự kiện trong tuần</p>
            </div>
            
            <div class="alert alert-success" id="alertSuccess" style="display: none;"></div>
            <div class="alert alert-error" id="alertError" style="display: none;"></div>
            
            <button class="btn btn-primary" onclick="showAddForm()" style="margin-bottom: 20px;">
                <i class="fas fa-plus"></i> Thêm Sự Kiện Mới
            </button>
            
            <div id="eventsList"></div>
            <div class="loading" id="loading">Đang tải...</div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
        <div style="max-width: 600px; margin: 50px auto; background: #16213e; padding: 30px; border-radius: 10px;">
            <h3 style="color: #e8c088; margin-bottom: 20px;" id="modalTitle">Thêm Sự Kiện</h3>
            <form id="eventForm">
                <input type="hidden" id="eventId" name="event_id">
                
                <div class="form-group">
                    <label for="eventTitle">Tên Sự Kiện *</label>
                    <input type="text" id="eventTitle" name="event_title" required placeholder="Ví dụ: Boss Bang Hội">
                </div>
                
                <div class="form-group">
                    <label for="eventTime">Thời Gian *</label>
                    <input type="text" id="eventTime" name="event_time" required placeholder="Ví dụ: 19:30 hoặc 11:00 - 21:00">
                    <small>Nhập thời gian diễn ra sự kiện</small>
                </div>
                
                <div class="form-group">
                    <label for="eventDay">Ngày Trong Tuần *</label>
                    <input type="text" id="eventDay" name="event_day" required placeholder="Ví dụ: Thứ 2-4-6 hoặc Hàng Ngày">
                    <small>Nhập các ngày trong tuần</small>
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
        
        // Load events on page load
        document.addEventListener('DOMContentLoaded', loadEvents);
        
        async function loadEvents() {
            document.getElementById('loading').style.display = 'block';
            try {
                const res = await fetch('../api/cms/weekly_events.php');
                const data = await res.json();
                if (data.success) {
                    displayEvents(data.data);
                } else {
                    showError('Lỗi: ' + data.error);
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
        
        function displayEvents(events) {
            const list = document.getElementById('eventsList');
            if (events.length === 0) {
                list.innerHTML = '<div style="text-align: center; padding: 40px; color: #87ceeb;">Chưa có sự kiện nào</div>';
                return;
            }
            
            list.innerHTML = events.map(e => `
                <div class="event-item">
                    <div class="event-header">
                        <div>
                            <div class="event-title">${escapeHtml(e.event_title)}</div>
                            <div class="event-meta">
                                <span><i class="fas fa-clock"></i> ${escapeHtml(e.event_time)}</span>
                                <span style="margin-left: 15px;"><i class="fas fa-calendar"></i> ${escapeHtml(e.event_day)}</span>
                                <span style="margin-left: 15px;"><i class="fas fa-sort-numeric-up"></i> Order: ${e.display_order}</span>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm" onclick="editEvent(${e.event_id})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteEvent(${e.event_id})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function showAddForm() {
            editingId = null;
            document.getElementById('modalTitle').textContent = 'Thêm Sự Kiện';
            document.getElementById('eventForm').reset();
            document.getElementById('eventId').value = '';
            document.getElementById('eventModal').style.display = 'block';
        }
        
        function editEvent(id) {
            editingId = id;
            document.getElementById('modalTitle').textContent = 'Sửa Sự Kiện';
            
            fetch(`../api/cms/weekly_events.php?event_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        const e = data.data;
                        document.getElementById('eventId').value = e.event_id;
                        document.getElementById('eventTitle').value = e.event_title;
                        document.getElementById('eventTime').value = e.event_time;
                        document.getElementById('eventDay').value = e.event_day;
                        document.getElementById('displayOrder').value = e.display_order || 0;
                        document.getElementById('eventModal').style.display = 'block';
                    } else {
                        showError('Không tìm thấy sự kiện');
                    }
                })
                .catch(e => showError('Lỗi: ' + e.message));
        }
        
        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
        }
        
        // Handle form submit
        document.getElementById('eventForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                event_title: document.getElementById('eventTitle').value.trim(),
                event_time: document.getElementById('eventTime').value.trim(),
                event_day: document.getElementById('eventDay').value.trim(),
                display_order: parseInt(document.getElementById('displayOrder').value) || 0
            };
            
            if (editingId) {
                formData.event_id = editingId;
            }
            
            try {
                const res = await fetch('../api/cms/weekly_events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const result = await res.json();
                if (result.success) {
                    showSuccess(editingId ? 'Đã cập nhật sự kiện!' : 'Đã thêm sự kiện!');
                    closeModal();
                    loadEvents();
                } else {
                    showError('Lỗi: ' + result.error);
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            }
        });
        
        async function deleteEvent(id) {
            if (!confirm('Xóa sự kiện này?')) return;
            
            try {
                const res = await fetch(`../api/cms/weekly_events.php?event_id=${id}`, {
                    method: 'DELETE'
                });
                const result = await res.json();
                if (result.success) {
                    showSuccess('Đã xóa sự kiện!');
                    loadEvents();
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
        
        // Close modal on outside click
        document.getElementById('eventModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

