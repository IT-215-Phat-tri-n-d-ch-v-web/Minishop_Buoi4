<?php
require __DIR__ . '/includes/init.php';
require_login();

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId || $orderId < 1) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

$userId = current_user()['id'];
$errors = [];
$success = '';

if (isset($_SESSION['order_success'])) {
    $success = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}
$clearCart = !empty($_SESSION['clear_cart_after_order']);
unset($_SESSION['clear_cart_after_order']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'SELECT id, status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE'
        );
        $stmt->execute([$orderId, $userId]);
        $lockedOrder = $stmt->fetch();

        if (!$lockedOrder) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Không tìm thấy đơn hàng.');
        }

        if ($lockedOrder['status'] !== 'pending') {
            $pdo->rollBack();
            $errors[] = 'Chỉ có thể hủy đơn hàng đang chờ xử lý.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT product_id, quantity FROM order_items WHERE order_id = ?'
            );
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll();
            $restoreStock = $pdo->prepare(
                'UPDATE products SET stock = stock + ? WHERE id = ?'
            );
            foreach ($items as $item) {
                $restoreStock->execute([(int) $item['quantity'], (int) $item['product_id']]);
            }

            $stmt = $pdo->prepare(
                "UPDATE orders SET status = 'cancelled'
                 WHERE id = ? AND user_id = ? AND status = 'pending'"
            );
            $stmt->execute([$orderId, $userId]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                $errors[] = 'Đơn hàng đã thay đổi trạng thái. Vui lòng tải lại trang.';
            } else {
                $pdo->commit();
                $_SESSION['order_success'] = 'Đơn hàng đã được hủy và tồn kho đã được cập nhật.';
                header('Location: ' . url('order-detail.php?id=' . $orderId));
                exit;
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Order cancellation failed: ' . $e->getMessage());
        $errors[] = 'Không thể hủy đơn hàng lúc này. Vui lòng thử lại sau.';
    }
}

$stmt = $pdo->prepare(
    'SELECT id, fullname, phone, address, note, total, status, created_at
     FROM orders WHERE id = ? AND user_id = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

$stmt = $pdo->prepare(
    'SELECT product_name, price, quantity
     FROM order_items WHERE order_id = ? ORDER BY id'
);
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();
$statuses = [
    'pending' => 'Chờ xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];

$pageTitle = 'Chi tiết đơn hàng #' . $orderId;
require __DIR__ . '/includes/header.php';
?>
<h2>Chi tiết đơn hàng #<?= (int) $order['id'] ?></h2>
<?php if ($clearCart): ?>
    <span hidden data-clear-cart></span>
<?php endif; ?>

<?php if ($success): ?>
    <p class="alert alert-success"><?= e($success) ?></p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endforeach; ?>

<p>Trạng thái: <strong><?= e($statuses[$order['status']] ?? $order['status']) ?></strong></p>
<p>Ngày đặt: <?= e($order['created_at']) ?></p>
<p>Người nhận: <?= e($order['fullname']) ?> - <?= e($order['phone']) ?></p>
<p>Địa chỉ: <?= e($order['address']) ?></p>
<?php if ($order['note'] !== ''): ?>
    <p>Ghi chú: <?= e($order['note']) ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><?= format_price($item['price']) ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td><?= format_price($item['price'] * $item['quantity']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="3">Tổng cộng</th><th><?= format_price($order['total']) ?></th></tr>
    </tfoot>
</table>

<?php if ($order['status'] === 'pending'): ?>
    <form action="<?= url('order-detail.php?id=' . (int) $order['id']) ?>"
          method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p><button type="submit" class="btn btn-danger"
                   onclick="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?')">
            Hủy đơn
        </button></p>
    </form>
<?php endif; ?>
<p><a href="<?= url('orders.php') ?>">&laquo; Quay lại đơn hàng</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
