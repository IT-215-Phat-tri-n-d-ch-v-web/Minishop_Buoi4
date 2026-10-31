<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Trang chủ') ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <script src="<?= url('js/main.js') ?>" defer></script>
</head>
<body>
    <header class="site-header">
        <h1><?= APP_NAME ?></h1>
        <nav class="main-nav">
            <a href="<?= url('index.php') ?>">Trang chủ</a>
            <a href="<?= url('about.php') ?>">Giới thiệu</a>
            <a href="<?= url('contact.php') ?>">Liên hệ</a>
            <a href="<?= url('cart.php') ?>">Giỏ hàng (<span id="cart-count">0</span>)</a>
            <?php if (current_user()): ?>
                <a href="<?= url('orders.php') ?>">Đơn hàng</a>
                <a href="<?= url('profile.php') ?>">Tài khoản</a>
                <a href="<?= url('logout.php') ?>">Đăng xuất</a>
            <?php else: ?>
                <a href="<?= url('login.php') ?>">Đăng nhập</a>
                <a href="<?= url('register.php') ?>">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
