<?php
/**
 * Authentication Helper
 * Hàm hỗ trợ xác thực và phân quyền
 */

/**
 * Kiểm tra user đã đăng nhập chưa
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Kiểm tra user có phải admin không
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['role']) && 
           $_SESSION['role'] === 'admin';
}

/**
 * Kiểm tra và redirect nếu chưa đăng nhập
 */
function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit();
    }
}

/**
 * Kiểm tra và redirect nếu không phải admin
 */
function requireAdmin($redirectTo = 'dashboard.php') {
    requireLogin('login.php');
    
    if (!isAdmin()) {
        $_SESSION['error'] = 'Bạn không có quyền truy cập trang này!';
        header('Location: ' . $redirectTo);
        exit();
    }
}

/**
 * Lấy user ID từ session
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Lấy username từ session
 */
function getUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Lấy role từ session
 */
function getUserRole() {
    return $_SESSION['role'] ?? 'user';
}

/**
 * Kiểm tra username có phải admin account không
 */
function isAdminAccount($username) {
    return strtolower($username) === 'adminsonglong';
}

