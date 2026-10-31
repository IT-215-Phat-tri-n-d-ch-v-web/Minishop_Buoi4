<?php
// Các hàm dùng chung của MiniShop.

// ===== 1. Hiển thị =====

// Mã hóa ký tự đặc biệt trước khi in ra HTML (chống XSS)
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Định dạng tiền: 250000 -> "250.000 đ"
function format_price($amount)
{
    return number_format((int) $amount, 0, ',', '.') . ' đ';
}

// Tạo đường dẫn tuyệt đối trong website: url('cart.php') -> "/minishop/cart.php"
function url($path = '')
{
    return BASE_URL . ltrim($path, '/');
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function is_valid_csrf_token($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (current_user()) {
        return;
    }

    $basePath = parse_url(BASE_URL, PHP_URL_PATH);
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (is_string($basePath) && strpos($requestUri, $basePath) === 0) {
        $_SESSION['return_to'] = $requestUri;
    }

    header('Location: ' . url('login.php'));
    exit;
}

function redirect_after_login($path)
{
    $basePath = parse_url(BASE_URL, PHP_URL_PATH);
    $targetPath = is_string($path) ? parse_url($path, PHP_URL_PATH) : false;

    if (
        is_string($basePath)
        && is_string($targetPath)
        && strpos($targetPath, $basePath) === 0
        && strpos($path, '//') !== 0
        && strpos($path, "\r") === false
        && strpos($path, "\n") === false
    ) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url('index.php'));
    }
    exit;
}

function verify_csrf_or_fail()
{
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}

function post_string($key)
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? $value : '';
}
