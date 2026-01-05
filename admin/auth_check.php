<?php
/**
 * Admin Authentication Check
 * File này cần được require ở đầu mỗi trang admin
 */
session_start();
require_once __DIR__ . '/../includes/auth_helper.php';

// Kiểm tra quyền admin
requireAdmin('../dashboard.php');

