<?php
// Tệp khởi động: mọi trang đều nạp tệp này ở dòng đầu tiên.

require_once __DIR__ . '/../config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Hiển thị lỗi khi phát triển, ẩn lỗi và ghi nhật ký khi chạy thật
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';
