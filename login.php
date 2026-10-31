<?php
require __DIR__ . '/includes/init.php';

$errors = [];
$email = '';
$success = '';

if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $email = trim(post_string('email'));
    $password = post_string('password');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    }
    if (!is_string($password) || $password === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT id, fullname, email, phone, address, role, password_hash
             FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            $returnTo = $_SESSION['return_to'] ?? url('index.php');
            unset($_SESSION['return_to']);
            redirect_after_login($returnTo);
        }

        $errors[] = 'Email hoặc mật khẩu không chính xác.';
    }
}

$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<h2>Đăng nhập</h2>

<?php if ($success): ?>
    <p class="alert alert-success"><?= e($success) ?></p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endforeach; ?>

<form action="<?= url('login.php') ?>" method="post" class="form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
    </p>
    <p>
        <label for="password">Mật khẩu</label><br>
        <input type="password" id="password" name="password" required>
    </p>
    <p>
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">Ghi nhớ đăng nhập</label>
    </p>
    <p>
        <button type="submit" class="btn">Đăng nhập</button>
    </p>
    <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
