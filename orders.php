<?php
require __DIR__ . '/includes/init.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT id, total, status, created_at
     FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC'
);
$stmt->execute([current_user()['id']]);
$orders = $stmt->fetchAll();
$statuses = [
    'pending' => 'Chờ xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];

$pageTitle = 'Đơn hàng của tôi';
require __DIR__ . '/includes/header.php';
?>
<h2>Đơn hàng của tôi</h2>

<?php if (!$orders): ?>
    <p>Bạn chưa có đơn hàng nào.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><?= e($order['created_at']) ?></td>
                    <td><?= format_price($order['total']) ?></td>
                    <td><?= e($statuses[$order['status']] ?? $order['status']) ?></td>
                    <td>
                        <a href="<?= url('order-detail.php?id=' . (int) $order['id']) ?>">
                            Xem chi tiết
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
