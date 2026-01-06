<?php
/**
 * CMS Admin - Server Info
 * Quản lý thông tin server (text area)
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Server - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    <style>
        .form-container {
            background: #16213e;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            max-width: 900px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #e8c088;
            font-weight: 600;
        }
        
        .form-group textarea {
            width: 100%;
            min-height: 300px;
            padding: 15px;
            background: #0f1624;
            border: 1px solid #4682b4;
            border-radius: 5px;
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            resize: vertical;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #e8c088;
            box-shadow: 0 0 10px rgba(232, 192, 136, 0.2);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #e8c088;
            color: #1a1a2e;
        }
        
        .btn-primary:hover {
            background: #ffd700;
        }
        
        .btn-secondary {
            background: #4682b4;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background: #5a9fd4;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert-success {
            background: #2d5a3d;
            color: #90ee90;
            border: 1px solid #4caf50;
        }
        
        .alert-error {
            background: #5a2d2d;
            color: #ff6b6b;
            border: 1px solid #f44336;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #87ceeb;
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
                <li><a href="server_info.php" class="active"><i class="fas fa-server"></i> Thông Tin Server</a></li>
                <li><a href="weekly_events.php"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="tichnap/index.php"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-server"></i> Thông Tin Server</h2>
                <p>Chỉnh sửa thông tin server (copy/paste text)</p>
            </div>
            
            <div class="form-container">
                <div class="alert alert-success" id="alertSuccess">
                    <i class="fas fa-check-circle"></i> <span id="alertMessage"></span>
                </div>
                <div class="alert alert-error" id="alertError">
                    <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
                </div>
                
                <form id="serverInfoForm">
                    <div class="form-group">
                        <label for="content">
                            <i class="fas fa-file-alt"></i> Nội Dung Thông Tin Server
                        </label>
                        <textarea id="content" name="content" placeholder="Cấp Độ: 100&#10;Chủng Tộc: Á Châu - Âu Châu&#10;Kỹ Năng: 300&#10;..."></textarea>
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
                
                <div class="loading" id="loading">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
        });
        
        // Load server info
        async function loadData() {
            const loading = document.getElementById('loading');
            const content = document.getElementById('content');
            
            loading.style.display = 'block';
            content.disabled = true;
            
            try {
                const response = await fetch('../api/cms/server_info.php');
                const result = await response.json();
                
                if (result.success) {
                    content.value = result.data.content || '';
                } else {
                    showError('Không thể tải dữ liệu: ' + result.error);
                }
            } catch (error) {
                showError('Lỗi kết nối: ' + error.message);
            } finally {
                loading.style.display = 'none';
                content.disabled = false;
            }
        }
        
        // Handle form submit
        document.getElementById('serverInfoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const content = document.getElementById('content').value.trim();
            
            if (!content) {
                showError('Vui lòng nhập nội dung');
                return;
            }
            
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            
            try {
                const response = await fetch('../api/cms/server_info.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ content: content })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Đã lưu thông tin server thành công!');
                } else {
                    showError('Lỗi: ' + result.error);
                }
            } catch (error) {
                showError('Lỗi kết nối: ' + error.message);
            } finally {
                loading.style.display = 'none';
            }
        });
        
        function showSuccess(message) {
            const alert = document.getElementById('alertSuccess');
            const messageEl = document.getElementById('alertMessage');
            messageEl.textContent = message;
            alert.style.display = 'block';
            document.getElementById('alertError').style.display = 'none';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        }
        
        function showError(message) {
            const alert = document.getElementById('alertError');
            const messageEl = document.getElementById('errorMessage');
            messageEl.textContent = message;
            alert.style.display = 'block';
            document.getElementById('alertSuccess').style.display = 'none';
        }
    </script>
</body>
</html>

