<?php
/**
 * CMS Admin - QR Code Management
 * Upload ảnh QR code
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    <style>
        .qr-preview {
            max-width: 300px;
            max-height: 300px;
            margin: 20px 0;
            border-radius: 10px;
            border: 2px solid #4682b4;
        }
        
        .upload-area {
            border: 2px dashed #4682b4;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #0f1624;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: #e8c088;
            background: #16213e;
        }
        
        .upload-area.dragover {
            border-color: #e8c088;
            background: #16213e;
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
                <li><a href="qrcode.php" class="active"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Quản Lý User</a></li>
                <li><a href="tichnap/index.php"><i class="fas fa-gift"></i> Mốc Nạp Tích Lũy</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-qrcode"></i> QR Code</h2>
                <p>Upload ảnh QR code để hiển thị trên trang chủ</p>
            </div>
            
            <div class="alert alert-success" id="alertSuccess" style="display: none;"></div>
            <div class="alert alert-error" id="alertError" style="display: none;"></div>
            
            <div class="form-container">
                <form id="qrcodeForm">
                    <div class="form-group">
                        <label for="description">Mô Tả</label>
                        <input type="text" id="description" name="description" placeholder="Ví dụ: Quét code vào cộng đồng">
                    </div>
                    
                    <div class="form-group">
                        <label>Ảnh QR Code *</label>
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #87ceeb; margin-bottom: 15px;"></i>
                            <p style="color: #87ceeb; margin-bottom: 10px;">Kéo thả ảnh vào đây hoặc click để chọn</p>
                            <p style="color: #87ceeb; font-size: 0.9rem;">Hỗ trợ: JPG, PNG, GIF, WEBP (tối đa 10MB, khuyến nghị 5MB)</p>
                            <input type="file" id="imageFile" name="image" accept="image/*" style="display: none;">
                        </div>
                        <div id="imagePreview" style="text-align: center; display: none;">
                            <img id="previewImg" class="qr-preview" alt="QR Code Preview">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeImage()" style="margin-top: 10px;">
                                <i class="fas fa-trash"></i> Xóa Ảnh
                            </button>
                        </div>
                        <input type="hidden" id="currentImagePath" name="current_image_path">
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu QR Code
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
        let currentImageFile = null;
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadData);
        
        // Setup upload area
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('imageFile');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        function handleFileSelect(file) {
            if (!file.type.startsWith('image/')) {
                showError('Vui lòng chọn file ảnh');
                return;
            }
            
            // Check file size (10MB max, but recommend 5MB)
            const maxSize = 10 * 1024 * 1024; // 10MB
            const recommendedSize = 5 * 1024 * 1024; // 5MB
            
            if (file.size > maxSize) {
                showError(`File quá lớn (${(file.size / 1024 / 1024).toFixed(2)}MB). Tối đa 10MB. Vui lòng nén ảnh hoặc chọn file nhỏ hơn.`);
                return;
            }
            
            if (file.size > recommendedSize) {
                if (!confirm(`File khá lớn (${(file.size / 1024 / 1024).toFixed(2)}MB). Khuyến nghị tối đa 5MB để tải nhanh hơn. Bạn có muốn tiếp tục?`)) {
                    return;
                }
            }
            
            currentImageFile = file;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
                uploadArea.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
        
        function removeImage() {
            fileInput.value = '';
            document.getElementById('imagePreview').style.display = 'none';
            uploadArea.style.display = 'block';
            currentImageFile = null;
        }
        
        async function loadData() {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            
            try {
                const res = await fetch('../api/cms/qrcode.php');
                const data = await res.json();
                
                if (data.success && data.data) {
                    const qr = data.data;
                    document.getElementById('description').value = qr.description || '';
                    document.getElementById('currentImagePath').value = qr.image_path || '';
                    
                    if (qr.image_path) {
                        const preview = document.getElementById('imagePreview');
                        const img = document.getElementById('previewImg');
                        img.src = '../' + qr.image_path;
                        img.onload = function() {
                            preview.style.display = 'block';
                            uploadArea.style.display = 'none';
                        };
                    }
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Handle form submit
        document.getElementById('qrcodeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!currentImageFile && !document.getElementById('currentImagePath').value) {
                showError('Vui lòng chọn ảnh QR code');
                return;
            }
            
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            
            try {
                const formData = new FormData();
                formData.append('description', document.getElementById('description').value.trim());
                
                if (currentImageFile) {
                    formData.append('image', currentImageFile);
                } else {
                    // If no new file, send existing path (for backward compatibility)
                    formData.append('image_path', document.getElementById('currentImagePath').value);
                }
                
                const res = await fetch('../api/cms/qrcode.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await res.json();
                if (result.success) {
                    showSuccess('Đã lưu QR code thành công!');
                    if (result.data && result.data.image_path) {
                        document.getElementById('currentImagePath').value = result.data.image_path;
                    }
                    currentImageFile = null; // Reset after successful upload
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

