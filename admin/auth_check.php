<?php
/**
 * Admin Authentication Check
 * File này cần được require ở đầu mỗi trang admin
 */
session_start();
require_once __DIR__ . '/../includes/auth_helper.php';

// Kiểm tra đăng nhập trước - redirect về admin/login.php nếu chưa đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Kiểm tra quyền admin - redirect về dashboard nếu không phải admin
if (!isAdmin()) {
    $_SESSION['error'] = 'Bạn không có quyền truy cập trang này!';
    header('Location: ../dashboard.php');
    exit();
}

