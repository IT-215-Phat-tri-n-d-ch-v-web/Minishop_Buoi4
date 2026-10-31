<?php
require __DIR__ . '/includes/init.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !current_user()
    && is_valid_csrf_token($_POST['csrf_token'] ?? null)
) {
    $_SESSION['pending_order_cart'] = post_string('cart_json');
}
require_login();

$errors = [];
$cartJson = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? post_string('cart_json')
    : ($_SESSION['pending_order_cart'] ?? '');
$cart = json_decode($cartJson, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    if (!is_array($cart) || !$cart) {
        $errors[] = 'Giỏ hàng đang trống hoặc không hợp lệ.';
    } else {
        $quantities = [];
        foreach ($cart as $productId => $quantity) {
            $idString = (string) $productId;
            if (
                !ctype_digit($idString)
                || (string) (int) $idString !== $idString
                || (int) $idString < 1
                || !is_int($quantity)
                || $quantity < 1
                || $quantity > 99
            ) {
                $errors[] = 'Sản phẩm hoặc số lượng trong giỏ hàng không hợp lệ.';
                break;
            }
            $quantities[(int) $idString] = $quantity;
        }

        if (!$errors) {
            ksort($quantities);
            $placeholders = implode(',', array_fill(0, count($quantities), '?'));

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    "SELECT id, name, price, stock FROM products
                     WHERE id IN ($placeholders) ORDER BY id FOR UPDATE"
                );
                $stmt->execute(array_keys($quantities));
                $products = $stmt->fetchAll();

                if (count($products) !== count($quantities)) {
                    $errors[] = 'Một hoặc nhiều sản phẩm không còn tồn tại.';
                } else {
                    $total = 0;
                    foreach ($products as $product) {
                        $quantity = $quantities[(int) $product['id']];
                        if ($quantity > (int) $product['stock']) {
                            $errors[] = 'Sản phẩm "' . $product['name'] . '" không đủ tồn kho.';
                            break;
                        }
                        $total += (int) $product['price'] * $quantity;
                    }

                    if ($total < 1 || $total > 4294967295) {
                        $errors[] = 'Tổng tiền đơn hàng không hợp lệ.';
                    }
                }

                if ($errors) {
                    $pdo->rollBack();
                } else {
                    $user = current_user();
                    $stmt = $pdo->prepare(
                        'INSERT INTO orders (user_id, fullname, phone, address, total)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        $user['id'],
                        $user['fullname'],
                        $user['phone'],
                        $user['address'],
                        $total,
                    ]);
                    $orderId = (int) $pdo->lastInsertId();

                    $insertItem = $pdo->prepare(
                        'INSERT INTO order_items
                         (order_id, product_id, product_name, price, quantity)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $decreaseStock = $pdo->prepare(
                        'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
                    );
                    foreach ($products as $product) {
                        $productId = (int) $product['id'];
                        $quantity = $quantities[$productId];
                        $insertItem->execute([
                            $orderId,
                            $productId,
                            $product['name'],
                            $product['price'],
                            $quantity,
                        ]);
                        $decreaseStock->execute([$quantity, $productId, $quantity]);
                        if ($decreaseStock->rowCount() !== 1) {
                            $errors[] = 'Tồn kho vừa thay đổi. Vui lòng đặt hàng lại.';
                            break;
                        }
                    }

                    if ($errors) {
                        $pdo->rollBack();
                    } else {
                        $pdo->commit();
                        unset($_SESSION['pending_order_cart']);
                        $_SESSION['clear_cart_after_order'] = true;
                        header('Location: ' . url('order-detail.php?id=' . $orderId));
                        exit;
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Checkout failed: ' . $e->getMessage());
                $errors[] = 'Không thể tạo đơn hàng lúc này. Vui lòng thử lại sau.';
            }
        }
    }
}

$summary = [];
if (is_array($cart) && $cart) {
    $validIds = [];
    foreach ($cart as $productId => $quantity) {
        if (ctype_digit((string) $productId) && (int) $productId > 0) {
            $validIds[] = (int) $productId;
        }
    }
    if ($validIds) {
        $placeholders = implode(',', array_fill(0, count($validIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, name, price FROM products WHERE id IN ($placeholders) ORDER BY id"
        );
        $stmt->execute($validIds);
        $summary = $stmt->fetchAll();
    }
}

$pageTitle = 'Xác nhận đơn hàng';
require __DIR__ . '/includes/header.php';
?>
<h2>Xác nhận đơn hàng</h2>

<?php foreach ($errors as $error): ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endforeach; ?>

<?php if (!$summary): ?>
    <p>Không có sản phẩm hợp lệ trong giỏ hàng. Vui lòng quay lại giỏ hàng.</p>
    <p><a href="<?= url('cart.php') ?>">Quay lại giỏ hàng</a></p>
<?php else: ?>
    <table>
        <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th></tr></thead>
        <tbody>
            <?php foreach ($summary as $product): ?>
                <tr>
                    <td><?= e($product['name']) ?></td>
                    <td><?= format_price($product['price']) ?></td>
                    <td><?= (int) ($cart[$product['id']] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p>Thông tin nhận hàng: <?= e(current_user()['fullname']) ?>,
        <?= e(current_user()['phone']) ?>,
        <?= e(current_user()['address']) ?></p>
    <form action="<?= url('checkout.php') ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="cart_json" value="<?= e($cartJson) ?>">
        <button type="submit" class="btn btn-accent">Xác nhận đặt hàng</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
