<?php
/**
 * CMS API - Home Slider
 * GET: List sliders
 * POST: Create/Update slider (supports file upload or image_path)
 * DELETE: Delete slider (via query param ?slider_id=)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/upload_config.php';
require_once __DIR__ . '/../../connection_manager.php';

// Helper function to handle file upload
function handleImageUpload($file, $uploadDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error'];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed'];
    }
    
    // Validate file size (max 10MB, but recommend 5MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    $recommendedSize = 5 * 1024 * 1024; // 5MB
    $fileSize = $file['size'];
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    
    if ($fileSize > $maxSize) {
        return ['success' => false, 'error' => "File quá lớn ({$fileSizeMB}MB). Tối đa 10MB. Vui lòng nén ảnh hoặc chọn file nhỏ hơn."];
    }
    
    // Create upload directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'slider_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    $relativePath = 'uploads/slider/' . $filename;
    return ['success' => true, 'path' => $relativePath];
}

try {
    $db = ConnectionManager::getAccountDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get single slider by ID or all sliders
        $sliderID = isset($_GET['slider_id']) ? (int)$_GET['slider_id'] : null;
        
        if ($sliderID) {
            // Get single slider
            $stmt = $db->prepare("SELECT SliderID, ImagePath, LinkURL, DisplayOrder, IsActive, CreatedDate, UpdatedDate FROM TB_HomeSlider WHERE SliderID = ?");
            $stmt->execute([$sliderID]);
            $slider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($slider) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'slider_id' => (int)$slider['SliderID'],
                        'image_path' => $slider['ImagePath'],
                        'link_url' => $slider['LinkURL'],
                        'display_order' => (int)$slider['DisplayOrder'],
                        'is_active' => (bool)$slider['IsActive'],
                        'created_date' => $slider['CreatedDate'],
                        'updated_date' => $slider['UpdatedDate']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Slider not found'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            // Get all sliders
            $stmt = $db->query("SELECT SliderID, ImagePath, LinkURL, DisplayOrder, IsActive, CreatedDate, UpdatedDate FROM TB_HomeSlider ORDER BY DisplayOrder ASC, SliderID ASC");
            $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => array_map(function($slider) {
                    return [
                        'slider_id' => (int)$slider['SliderID'],
                        'image_path' => $slider['ImagePath'],
                        'link_url' => $slider['LinkURL'],
                        'display_order' => (int)$slider['DisplayOrder'],
                        'is_active' => (bool)$slider['IsActive'],
                        'created_date' => $slider['CreatedDate'],
                        'updated_date' => $slider['UpdatedDate']
                    ];
                }, $sliders)
            ], JSON_UNESCAPED_UNICODE);
        }
    } 
    elseif ($method === 'POST') {
        $imagePath = null;
        $linkURL = null;
        $displayOrder = 0;
        $isActive = true;
        $sliderID = null;
        $isFileUpload = false;
        
        // Check if this is multipart/form-data (file upload)
        if (isset($_FILES['image'])) {
            $uploadError = $_FILES['image']['error'];
            $fileSize = $_FILES['image']['size'] ?? 0;
            
            if ($uploadError !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File vượt quá upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
                    UPLOAD_ERR_FORM_SIZE => 'File vượt quá MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                    UPLOAD_ERR_NO_FILE => 'Không có file được upload',
                    UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                    UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
                    UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension'
                ];
                $errorMsg = isset($errorMessages[$uploadError]) ? $errorMessages[$uploadError] : 'Lỗi upload không xác định (error code: ' . $uploadError . ')';
                
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Upload file thất bại: ' . $errorMsg . '. File size: ' . round($fileSize / 1024 / 1024, 2) . 'MB. PHP limit: ' . ini_get('upload_max_filesize') . ', POST limit: ' . ini_get('post_max_size') . '. Vui lòng tăng upload_max_filesize lên 10M trong .htaccess hoặc php.ini'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $uploadDir = __DIR__ . '/../../uploads/slider';
            $uploadResult = handleImageUpload($_FILES['image'], $uploadDir);
            
            if (!$uploadResult['success']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $uploadResult['error']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $imagePath = $uploadResult['path'];
            $isFileUpload = true;
            $linkURL = isset($_POST['link_url']) ? trim($_POST['link_url']) : null;
            $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
            $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
            $sliderID = isset($_POST['slider_id']) ? (int)$_POST['slider_id'] : null;
        } else {
            // Handle JSON input (for backward compatibility) or FormData without file
            $input = null;
            
            if (!empty($_POST)) {
                $input = $_POST;
            } else {
                $jsonInput = file_get_contents('php://input');
                if (!empty($jsonInput)) {
                    $input = json_decode($jsonInput, true);
                }
            }
            
            if ($input) {
                $sliderID = isset($input['slider_id']) ? (int)$input['slider_id'] : null;
                $linkURL = isset($input['link_url']) ? trim($input['link_url']) : null;
                $displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;
                $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
                
                // Get image_path from input
                $imagePath = isset($input['image_path']) ? trim($input['image_path']) : null;
                
                // If updating and no image_path provided, get from existing record
                if ($sliderID && (!$imagePath || trim($imagePath) === '')) {
                    $stmt = $db->prepare("SELECT ImagePath FROM TB_HomeSlider WHERE SliderID = ?");
                    $stmt->execute([$sliderID]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existing) {
                        $imagePath = $existing['ImagePath'];
                    }
                }
            }
            
            // Validate: require image_path for new sliders
            // But also check if there was a file upload attempt that failed
            if (!$sliderID) {
                // For new sliders, must have either file upload or image_path
                if (!$imagePath || trim($imagePath) === '') {
                    // Check if there was a file upload attempt that failed
                    if (isset($_FILES['image'])) {
                        $uploadError = $_FILES['image']['error'];
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'File vượt quá kích thước tối đa (upload_max_filesize)',
                            UPLOAD_ERR_FORM_SIZE => 'File vượt quá kích thước tối đa (MAX_FILE_SIZE)',
                            UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                            UPLOAD_ERR_NO_FILE => 'Không có file được upload. Vui lòng chọn file.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
                            UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension'
                        ];
                        $errorMsg = isset($errorMessages[$uploadError]) ? $errorMessages[$uploadError] : 'Lỗi upload không xác định (error code: ' . $uploadError . ')';
                        
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Upload file thất bại: ' . $errorMsg
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    } else {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Vui lòng upload ảnh slider hoặc cung cấp image_path'
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
            }
        }
        
        // Validate URL if provided
        if ($linkURL && !filter_var($linkURL, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid link_url format'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Get old image path if updating
        $oldImagePath = null;
        if ($sliderID) {
            $stmt = $db->prepare("SELECT ImagePath FROM TB_HomeSlider WHERE SliderID = ?");
            $stmt->execute([$sliderID]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($old) {
                $oldImagePath = $old['ImagePath'];
            }
        }
        
        if ($sliderID) {
            // Update existing
            $stmt = $db->prepare("UPDATE TB_HomeSlider SET ImagePath = ?, LinkURL = ?, DisplayOrder = ?, IsActive = ?, UpdatedDate = GETDATE() WHERE SliderID = ?");
            $stmt->execute([$imagePath, $linkURL, $displayOrder, $isActive ? 1 : 0, $sliderID]);
            
            // Delete old image if new image uploaded
            if ($oldImagePath && $isFileUpload && file_exists(__DIR__ . '/../../' . $oldImagePath)) {
                @unlink(__DIR__ . '/../../' . $oldImagePath);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Slider updated successfully',
                'slider_id' => $sliderID,
                'data' => [
                    'image_path' => $imagePath
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO TB_HomeSlider (ImagePath, LinkURL, DisplayOrder, IsActive, CreatedDate, UpdatedDate) VALUES (?, ?, ?, ?, GETDATE(), GETDATE())");
            $stmt->execute([$imagePath, $linkURL, $displayOrder, $isActive ? 1 : 0]);
            
            $newID = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Slider created successfully',
                'slider_id' => (int)$newID,
                'data' => [
                    'image_path' => $imagePath
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    elseif ($method === 'DELETE') {
        // Delete slider
        $sliderID = isset($_GET['slider_id']) ? (int)$_GET['slider_id'] : null;
        
        if (!$sliderID) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'slider_id parameter is required'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Get image path before deleting
        $stmt = $db->prepare("SELECT ImagePath FROM TB_HomeSlider WHERE SliderID = ?");
        $stmt->execute([$sliderID]);
        $slider = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM TB_HomeSlider WHERE SliderID = ?");
        $stmt->execute([$sliderID]);
        
        if ($stmt->rowCount() > 0) {
            // Delete image file
            if ($slider && file_exists(__DIR__ . '/../../' . $slider['ImagePath'])) {
                @unlink(__DIR__ . '/../../' . $slider['ImagePath']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Slider deleted successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Slider not found'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
