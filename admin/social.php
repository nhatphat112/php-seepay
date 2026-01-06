<?php
/**
 * CMS Admin - Social Links Management
 * Quản lý link FB, Zalo, Group FB, Discord
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Links - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
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
                <li><a href="social.php" class="active"><i class="fas fa-share-alt"></i> Social Links</a></li>
                <li><a href="server_info.php"><i class="fas fa-server"></i> Thông Tin Server</a></li>
                <li><a href="weekly_events.php"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="tichnap/index.php"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-share-alt"></i> Social Links</h2>
                <p>Quản lý link Facebook, Zalo, Group Facebook, Discord</p>
            </div>
            
            <div class="alert alert-success" id="alertSuccess" style="display: none;"></div>
            <div class="alert alert-error" id="alertError" style="display: none;"></div>
            
            <div class="form-container">
                <form id="socialForm">
                    <div class="form-group">
                        <label for="facebook">
                            <i class="fab fa-facebook"></i> Facebook Page URL
                        </label>
                        <input type="url" id="facebook" name="facebook" placeholder="https://www.facebook.com/...">
                    </div>
                    
                    <div class="form-group">
                        <label for="facebook_group">
                            <i class="fab fa-facebook-square"></i> Facebook Group URL
                        </label>
                        <input type="url" id="facebook_group" name="facebook_group" placeholder="https://www.facebook.com/groups/...">
                    </div>
                    
                    <div class="form-group">
                        <label for="zalo">
                            <i class="fas fa-comments"></i> Zalo URL
                        </label>
                        <input type="url" id="zalo" name="zalo" placeholder="https://zalo.me/...">
                    </div>
                    
                    <div class="form-group">
                        <label for="discord">
                            <i class="fab fa-discord"></i> Discord URL
                        </label>
                        <input type="url" id="discord" name="discord" placeholder="https://discord.gg/...">
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu Thay Đổi
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="loadData()">
                            <i class="fas fa-sync"></i> Tải Lại
                        </button>
                    </div>
                </form>
                
                <div class="loading" id="loading">Đang tải...</div>
            </div>
        </main>
    </div>
    
    <script>
        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadData);
        
        async function loadData() {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            
            try {
                const res = await fetch('../api/cms/social.php');
                const data = await res.json();
                
                if (data.success) {
                    const links = data.data || {};
                    
                    // Fill form fields with loaded data
                    document.getElementById('facebook').value = links.facebook?.url || links['facebook']?.url || '';
                    document.getElementById('facebook_group').value = links.facebook_group?.url || links['facebook_group']?.url || '';
                    document.getElementById('zalo').value = links.zalo?.url || links['zalo']?.url || '';
                    document.getElementById('discord').value = links.discord?.url || links['discord']?.url || '';
                } else {
                    showError('Lỗi: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Handle form submit
        document.getElementById('socialForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                facebook: document.getElementById('facebook').value.trim(),
                facebook_group: document.getElementById('facebook_group').value.trim(),
                zalo: document.getElementById('zalo').value.trim(),
                discord: document.getElementById('discord').value.trim()
            };
            
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            
            try {
                const res = await fetch('../api/cms/social.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const result = await res.json();
                if (result.success) {
                    showSuccess('Đã lưu social links thành công!');
                } else {
                    showError('Lỗi: ' + result.error);
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            } finally {
                loading.style.display = 'none';
            }
        });
        
        function showSuccess(msg) {
            const el = document.getElementById('alertSuccess');
            el.textContent = msg;
            el.style.display = 'block';
            document.getElementById('alertError').style.display = 'none';
            setTimeout(() => el.style.display = 'none', 3000);
        }
        
        function showError(msg) {
            const el = document.getElementById('alertError');
            el.textContent = msg;
            el.style.display = 'block';
            document.getElementById('alertSuccess').style.display = 'none';
        }
    </script>
</body>
</html>

