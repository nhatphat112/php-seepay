<?php
/**
 * CMS Admin - Slider Management
 * Quản lý 5 ảnh slide
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slider Management - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_common.css">
    <style>
        .slider-item {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #0f1624;
            margin-bottom: 20px;
        }
        
        .slider-preview {
            max-width: 300px;
            max-height: 200px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .upload-area {
            border: 2px dashed #4682b4;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #0f1624;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        
        .upload-area:hover {
            border-color: #e8c088;
            background: #16213e;
        }
        
        .upload-area.dragover {
            border-color: #e8c088;
            background: #16213e;
        }
        
        .image-preview-small {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
            margin: 10px 0;
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
                <li><a href="slider.php" class="active"><i class="fas fa-images"></i> Slider (5 ảnh)</a></li>
                <li><a href="news.php"><i class="fas fa-newspaper"></i> Tin Bài</a></li>
                <li><a href="social.php"><i class="fas fa-share-alt"></i> Social Links</a></li>
                <li><a href="server_info.php"><i class="fas fa-server"></i> Thông Tin Server</a></li>
                <li><a href="weekly_events.php"><i class="fas fa-calendar-week"></i> Sự Kiện Trong Tuần</a></li>
                <li><a href="qrcode.php"><i class="fas fa-qrcode"></i> QR Code</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Lịch Sử Giao Dịch</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-images"></i> Slider Management</h2>
                <p>Quản lý 5 ảnh slide ở đầu trang</p>
            </div>
            
            <div class="alert alert-success" id="alertSuccess" style="display: none;"></div>
            <div class="alert alert-error" id="alertError" style="display: none;"></div>
            
            <button class="btn btn-primary" onclick="showAddForm()" style="margin-bottom: 20px;">
                <i class="fas fa-plus"></i> Thêm Slider Mới
            </button>
            
            <div id="slidersList"></div>
            <div class="loading" id="loading">Đang tải...</div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="sliderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
        <div style="max-width: 600px; margin: 50px auto; background: #16213e; padding: 30px; border-radius: 10px;">
            <h3 style="color: #e8c088; margin-bottom: 20px;" id="modalTitle">Thêm Slider</h3>
            <form id="sliderForm">
                <input type="hidden" id="sliderId" name="slider_id">
                <input type="hidden" id="currentImagePath" name="current_image_path">
                
                <div class="form-group">
                    <label>Ảnh Slider *</label>
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #87ceeb; margin-bottom: 10px;"></i>
                        <p style="color: #87ceeb; margin-bottom: 5px; font-size: 0.9rem;">Kéo thả ảnh vào đây hoặc click để chọn</p>
                        <p style="color: #87ceeb; font-size: 0.85rem;">JPG, PNG, GIF, WEBP</p>
                        <p style="color: #e8c088; font-size: 0.8rem; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Tối đa: <strong>10MB</strong> | Khuyến nghị: <strong>5MB</strong>
                        </p>
                        <input type="file" id="imageFile" name="image" accept="image/*" style="display: none;">
                    </div>
                    <div id="fileSizeInfo" style="display: none; margin-top: 10px; padding: 10px; background: #0f1624; border-radius: 5px; color: #87ceeb; font-size: 0.9rem;">
                        <i class="fas fa-file-image"></i> <span id="fileName"></span> 
                        (<span id="fileSize"></span>)
                    </div>
                    <div id="imagePreview" style="text-align: center; display: none;">
                        <img id="previewImg" class="image-preview-small" alt="Preview">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeImage()" style="margin-top: 10px;">
                            <i class="fas fa-trash"></i> Xóa Ảnh
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="linkUrl">Link URL</label>
                    <input type="url" id="linkUrl" name="link_url" placeholder="https://...">
                    <small>Link khi click vào slider (tùy chọn)</small>
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
        let currentImageFile = null;
        
        // Load sliders on page load
        document.addEventListener('DOMContentLoaded', loadSliders);
        
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
            
            // Show file info
            const fileSizeInfo = document.getElementById('fileSizeInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileSizeInfo.style.display = 'block';
            
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
                uploadArea.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        function removeImage() {
            fileInput.value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('fileSizeInfo').style.display = 'none';
            uploadArea.style.display = 'block';
            currentImageFile = null;
        }
        
        async function loadSliders() {
            document.getElementById('loading').style.display = 'block';
            try {
                const res = await fetch('../api/cms/slider.php');
                const data = await res.json();
                if (data.success) {
                    displaySliders(data.data);
                } else {
                    showError('Lỗi: ' + data.error);
                }
            } catch (e) {
                showError('Lỗi: ' + e.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
        
        function displaySliders(sliders) {
            const list = document.getElementById('slidersList');
            if (sliders.length === 0) {
                list.innerHTML = '<div style="text-align: center; padding: 40px; color: #87ceeb;">Chưa có slider nào</div>';
                return;
            }
            
            list.innerHTML = sliders.map(s => `
                <div class="slider-item">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h3 style="color: #e8c088; margin-bottom: 10px;">Slider #${s.slider_id}</h3>
                            <img src="../${s.image_path}" class="slider-preview" onerror="this.style.display='none'">
                            <p style="margin-top: 10px;"><strong>Link:</strong> ${s.link_url || 'Không có'}</p>
                            <p><strong>Order:</strong> ${s.display_order}</p>
                            <p><strong>Active:</strong> ${s.is_active ? 'Có' : 'Không'}</p>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm" onclick="editSlider(${s.slider_id})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteSlider(${s.slider_id})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function showAddForm() {
            editingId = null;
            currentImageFile = null;
            document.getElementById('modalTitle').textContent = 'Thêm Slider';
            document.getElementById('sliderForm').reset();
            document.getElementById('sliderId').value = '';
            document.getElementById('currentImagePath').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            uploadArea.style.display = 'block';
            document.getElementById('sliderModal').style.display = 'block';
        }
        
        function editSlider(id) {
            editingId = id;
            currentImageFile = null;
            document.getElementById('modalTitle').textContent = 'Sửa Slider';
            
            fetch(`../api/cms/slider.php?slider_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        const s = data.data;
                        document.getElementById('sliderId').value = s.slider_id;
                        document.getElementById('currentImagePath').value = s.image_path;
                        document.getElementById('linkUrl').value = s.link_url || '';
                        document.getElementById('displayOrder').value = s.display_order || 0;
                        
                        // Show existing image
                        if (s.image_path) {
                            const preview = document.getElementById('imagePreview');
                            const img = document.getElementById('previewImg');
                            img.src = '../' + s.image_path;
                            img.onload = function() {
                                preview.style.display = 'block';
                                uploadArea.style.display = 'none';
                            };
                        }
                        
                        document.getElementById('sliderModal').style.display = 'block';
                    } else {
                        showError('Không tìm thấy slider');
                    }
                })
                .catch(e => showError('Lỗi: ' + e.message));
        }
        
        function closeModal() {
            document.getElementById('sliderModal').style.display = 'none';
        }
        
        // Handle form submit
        document.getElementById('sliderForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!currentImageFile && !document.getElementById('currentImagePath').value) {
                showError('Vui lòng chọn ảnh slider');
                return;
            }
            
            const formData = new FormData();
            formData.append('link_url', document.getElementById('linkUrl').value.trim());
            formData.append('display_order', document.getElementById('displayOrder').value || 0);
            formData.append('is_active', '1');
            
            if (editingId) {
                formData.append('slider_id', editingId);
            }
            
            if (currentImageFile) {
                formData.append('image', currentImageFile);
            } else if (editingId) {
                // If updating and no new file, send existing path
                const existingPath = document.getElementById('currentImagePath').value;
                if (existingPath) {
                    formData.append('image_path', existingPath);
                }
            }
            // For new slider, must have file upload (already validated above)
            
            try {
                const res = await fetch('../api/cms/slider.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await res.json();
                if (result.success) {
                    showSuccess(editingId ? 'Đã cập nhật slider!' : 'Đã thêm slider!');
                    closeModal();
                    loadSliders();
                } else {
                    showError('Lỗi: ' + result.error);
                }
            } catch (e) {
                showError('Lỗi kết nối: ' + e.message);
            }
        });
        
        async function deleteSlider(id) {
            if (!confirm('Xóa slider này? Ảnh cũng sẽ bị xóa.')) return;
            
            try {
                const res = await fetch(`../api/cms/slider.php?slider_id=${id}`, {
                    method: 'DELETE'
                });
                const result = await res.json();
                if (result.success) {
                    showSuccess('Đã xóa slider!');
                    loadSliders();
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
        
        // Close modal on outside click
        document.getElementById('sliderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
