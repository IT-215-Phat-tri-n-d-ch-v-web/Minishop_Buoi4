<?php
require __DIR__ . '/includes/init.php';

$errors = [];
$fullname = '';
$email = '';
$subject = '';
$message = '';
$success = '';

if (isset($_SESSION['contact_success'])) {
    $success = $_SESSION['contact_success'];
    unset($_SESSION['contact_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $fullname = trim(post_string('fullname'));
    $email = trim(post_string('email'));
    $subject = trim(post_string('subject'));
    $message = trim(post_string('message'));

    if ($fullname === '' || mb_strlen($fullname) > 100) {
        $errors[] = 'Vui lòng nhập họ tên không quá 100 ký tự.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    }
    if ($subject === '' || mb_strlen($subject) > 200) {
        $errors[] = 'Tiêu đề không được để trống và tối đa 200 ký tự.';
    }
    if ($message === '') {
        $errors[] = 'Vui lòng nhập nội dung liên hệ.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO contacts (fullname, email, subject, message)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$fullname, $email, $subject, $message]);
        $_SESSION['contact_success'] = 'Cảm ơn bạn đã gửi liên hệ. Chúng tôi sẽ phản hồi sớm.';
        header('Location: ' . url('contact.php'));
        exit;
    }
}

$pageTitle = 'Liên hệ';
require __DIR__ . '/includes/header.php';
?>
<h2>Liên hệ</h2>

<?php if ($success): ?>
    <p class="alert alert-success"><?= e($success) ?></p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endforeach; ?>

<form action="<?= url('contact.php') ?>" method="post" class="form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <p>
        <label for="fullname">Họ và tên</label><br>
        <input type="text" id="fullname" name="fullname" maxlength="100"
               value="<?= e($fullname) ?>" required>
    </p>
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" maxlength="150"
               value="<?= e($email) ?>" required>
    </p>
    <p>
        <label for="subject">Tiêu đề</label><br>
        <input type="text" id="subject" name="subject" maxlength="200"
               value="<?= e($subject) ?>" required>
    </p>
    <p>
        <label for="message">Nội dung</label><br>
        <textarea id="message" name="message" rows="5" cols="40" required><?= e($message) ?></textarea>
    </p>
    <p>
        <button type="submit" class="btn">Gửi</button>
    </p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
