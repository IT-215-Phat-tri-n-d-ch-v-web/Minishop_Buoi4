<?php
require __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$errors = [];
$fullname = $user['fullname'];
$phone = $user['phone'];
$address = $user['address'];
$success = '';

if (isset($_SESSION['profile_success'])) {
    $success = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $fullname = trim(post_string('fullname'));
    $phone = trim(post_string('phone'));
    $address = trim(post_string('address'));

    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors[] = 'Họ và tên phải có từ 2 đến 100 ký tự.';
    }
    if (!preg_match('/^0[0-9]{9}$/', $phone)) {
        $errors[] = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.';
    }
    if (mb_strlen($address) > 255) {
        $errors[] = 'Địa chỉ không được vượt quá 255 ký tự.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?'
        );
        $stmt->execute([$fullname, $phone, $address, $user['id']]);
        $_SESSION['user']['fullname'] = $fullname;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['address'] = $address;
        $_SESSION['profile_success'] = 'Đã cập nhật thông tin tài khoản.';
        header('Location: ' . url('profile.php'));
        exit;
    }
}

$pageTitle = 'Tài khoản';
require __DIR__ . '/includes/header.php';
?>
<h2>Thông tin tài khoản</h2>

<?php if ($success): ?>
    <p class="alert alert-success"><?= e($success) ?></p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endforeach; ?>

<form action="<?= url('profile.php') ?>" method="post" class="form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <p>
        <label for="fullname">Họ và tên</label><br>
        <input type="text" id="fullname" name="fullname" maxlength="100"
               value="<?= e($fullname) ?>" required>
    </p>
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
    </p>
    <p>
        <label for="phone">Số điện thoại</label><br>
        <input type="tel" id="phone" name="phone" pattern="0[0-9]{9}"
               value="<?= e($phone) ?>" required>
    </p>
    <p>
        <label for="address">Địa chỉ</label><br>
        <textarea id="address" name="address" rows="3" maxlength="255"><?= e($address) ?></textarea>
    </p>
    <p><button type="submit" class="btn">Lưu thay đổi</button></p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
