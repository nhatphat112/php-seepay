<?php
/**
 * Upload Logger
 * Ghi log chi tiết quá trình upload để debug sử dụng error_log()
 */

class UploadLogger {
    public static function log($message, $data = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'message' => $message,
            'data' => $data,
            'server' => [
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
                'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
                'php_sapi' => php_sapi_name(),
            ],
            'php_config' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'max_input_time' => ini_get('max_input_time'),
                'memory_limit' => ini_get('memory_limit'),
                'file_uploads' => ini_get('file_uploads'),
            ],
            'post_data' => [
                'post_empty' => empty($_POST),
                'post_count' => count($_POST),
                'post_keys' => array_keys($_POST),
            ],
            'files_data' => [
                'files_empty' => empty($_FILES),
                'files_count' => count($_FILES),
                'files_keys' => array_keys($_FILES),
                'files_detail' => self::getFilesDetail(),
            ]
        ];
        
        $logLine = '[UPLOAD_LOG] ' . json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        error_log($logLine);
    }
    
    private static function getFilesDetail() {
        $detail = [];
        foreach ($_FILES as $key => $file) {
            $detail[$key] = [
                'name' => $file['name'] ?? 'N/A',
                'type' => $file['type'] ?? 'N/A',
                'size' => $file['size'] ?? 0,
                'size_mb' => isset($file['size']) ? round($file['size'] / 1024 / 1024, 2) : 0,
                'error' => $file['error'] ?? 'N/A',
                'error_message' => self::getUploadErrorMessage($file['error'] ?? null),
                'tmp_name' => $file['tmp_name'] ?? 'N/A',
                'tmp_exists' => isset($file['tmp_name']) && file_exists($file['tmp_name']),
            ];
        }
        return $detail;
    }
    
    private static function getUploadErrorMessage($errorCode) {
        if ($errorCode === null) return 'N/A';
        
        $messages = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
        ];
        
        return $messages[$errorCode] ?? 'Unknown error code: ' . $errorCode;
    }
    
    public static function logError($message, $error, $context = []) {
        $errorData = $error instanceof Exception ? [
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ] : $error;
        
        self::log($message, array_merge($context, ['error' => $errorData]));
    }
}

