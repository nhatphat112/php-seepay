<?php
/**
 * CMS API - QR Code
 * GET: Get QR code
 * POST: Update QR code (supports file upload or image_path)
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
    $filename = 'qrcode_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    $relativePath = 'uploads/qrcode/' . $filename;
    return ['success' => true, 'path' => $relativePath];
}

try {
    $db = ConnectionManager::getAccountDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get QR code
        $stmt = $db->query("SELECT TOP 1 QRCodeID, ImagePath, Description, UpdatedDate FROM TB_QRCode ORDER BY QRCodeID DESC");
        $qrcode = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($qrcode) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'qrcode_id' => (int)$qrcode['QRCodeID'],
                    'image_path' => $qrcode['ImagePath'],
                    'description' => $qrcode['Description'],
                    'updated_date' => $qrcode['UpdatedDate']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'qrcode_id' => null,
                    'image_path' => '',
                    'description' => null,
                    'updated_date' => null
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    } 
    elseif ($method === 'POST') {
        $imagePath = null;
        $description = null;
        
        // Check if this is multipart/form-data (file upload)
        if (isset($_FILES['image'])) {
            $uploadError = $_FILES['image']['error'];
            
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
                    'error' => 'Upload file thất bại: ' . $errorMsg . '. File size: ' . (isset($_FILES['image']['size']) ? round($_FILES['image']['size'] / 1024 / 1024, 2) . 'MB' : 'N/A') . '. PHP limit: ' . ini_get('upload_max_filesize') . ', POST limit: ' . ini_get('post_max_size')
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $uploadDir = __DIR__ . '/../../uploads/qrcode';
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
            $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        } else {
            // Handle JSON input (for backward compatibility)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['image_path']) || trim($input['image_path']) === '') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'image_path is required or upload an image file'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $imagePath = trim($input['image_path']);
            $description = isset($input['description']) ? trim($input['description']) : null;
        }
        
        // Check if record exists
        $stmt = $db->query("SELECT TOP 1 QRCodeID, ImagePath FROM TB_QRCode ORDER BY QRCodeID DESC");
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete old image if updating and new image uploaded
        if ($existing && isset($_FILES['image']) && file_exists(__DIR__ . '/../../' . $existing['ImagePath'])) {
            @unlink(__DIR__ . '/../../' . $existing['ImagePath']);
        }
        
        if ($existing) {
            // Update existing
            $stmt = $db->prepare("UPDATE TB_QRCode SET ImagePath = ?, Description = ?, UpdatedDate = GETDATE() WHERE QRCodeID = ?");
            $stmt->execute([$imagePath, $description, $existing['QRCodeID']]);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO TB_QRCode (ImagePath, Description, UpdatedDate) VALUES (?, ?, GETDATE())");
            $stmt->execute([$imagePath, $description]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'QR code updated successfully',
            'data' => [
                'image_path' => $imagePath
            ]
        ], JSON_UNESCAPED_UNICODE);
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
