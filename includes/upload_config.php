<?php
/**
 * Upload Configuration
 * Cấu hình upload file sử dụng ini_set()
 * 
 * Lưu ý: Một số cấu hình như upload_max_filesize và post_max_size
 * có thể không hoạt động với ini_set() trong PHP-FPM/CGI mode.
 * Trong trường hợp đó, cần cấu hình trong php.ini hoặc PHP-FPM pool config.
 */

// Cấu hình kích thước upload
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');

// Cấu hình thời gian thực thi
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

// Cấu hình bộ nhớ
ini_set('memory_limit', '256M');

?>

