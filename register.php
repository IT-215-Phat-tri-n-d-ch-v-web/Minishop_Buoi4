<?php
require __DIR__ . '/includes/init.php';

$errors = [];
$fullname = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $fullname = trim(post_string('fullname'));
    $email = trim(post_string('email'));
    $password = post_string('password');
    $password2 = post_string('password2');
    $phone = trim(post_string('phone'));
    $address = trim(post_string('address'));

    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors[] = 'Họ và tên phải có từ 2 đến 100 ký tự.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    }
    if (!is_string($password) || strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $password2) {
        $errors[] = 'Hai mật khẩu không khớp nhau.';
    }
    if (!preg_match('/^0[0-9]{9}$/', $phone)) {
        $errors[] = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email này đã được đăng ký.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (fullname, email, password_hash, phone, address)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $fullname,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $phone,
                $address,
            ]);
            $_SESSION['registration_success'] = 'Đăng ký thành công. Vui lòng đăng nhập.';
            header('Location: ' . url('login.php'));
            exit;
        }
    }
}

$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>
<h2>Đăng ký tài khoản</h2>

<?php foreach ($errors as $error): ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endforeach; ?>

<form action="<?= url('register.php') ?>" method="post" class="form" id="register-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <p>
        <label for="fullname">Họ và tên</label><br>
        <input type="text" id="fullname" name="fullname"
               required minlength="2" maxlength="100" placeholder="Nguyễn Văn An"
               value="<?= e($fullname) ?>">
    </p>
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email"
               required placeholder="ten@example.com" value="<?= e($email) ?>">
    </p>
    <p>
        <label for="password">Mật khẩu</label><br>
        <input type="password" id="password" name="password"
               required minlength="6">
    </p>
    <p>
        <label for="password2">Nhập lại mật khẩu</label><br>
        <input type="password" id="password2" name="password2"
               required minlength="6">
        <span class="error" id="password-error"></span>
    </p>
    <p>
        <label for="phone">Số điện thoại</label><br>
        <input type="tel" id="phone" name="phone"
               required pattern="0[0-9]{9}" placeholder="0900000000" value="<?= e($phone) ?>"
               title="Số điện thoại gồm 10 chữ số, bắt đầu bằng số 0">
    </p>
    <p>
        Giới tính<br>
        <input type="radio" id="male" name="gender" value="male">
        <label for="male">Nam</label>
        <input type="radio" id="female" name="gender" value="female">
        <label for="female">Nữ</label>
    </p>
    <p>
        <label for="province">Tỉnh / Thành phố</label><br>
        <select id="province" name="province">
            <option value="">-- Chọn --</option>
            <option value="dong-nai">Đồng Nai</option>
            <option value="hcm">TP. Hồ Chí Minh</option>
            <option value="ha-noi">Hà Nội</option>
            <option value="da-nang">Đà Nẵng</option>
        </select>
    </p>
    <p>
        <label for="address">Địa chỉ nhận hàng</label><br>
        <textarea id="address" name="address" rows="3" cols="40"><?= e($address) ?></textarea>
    </p>
    <p>
        <input type="checkbox" id="agree" name="agree" value="1" required>
        <label for="agree">Tôi đồng ý với điều khoản sử dụng</label>
    </p>
    <p>
        <button type="submit" class="btn">Đăng ký</button>
    </p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
