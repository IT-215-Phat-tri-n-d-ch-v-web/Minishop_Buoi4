<?php
require __DIR__ . '/includes/init.php';

$products = $pdo->query(
    'SELECT id, name, price, image, stock FROM products ORDER BY id'
)->fetchAll();

$pageTitle = 'Giỏ hàng';
require __DIR__ . '/includes/header.php';
?>
<h2>Giỏ hàng</h2>

<table class="cart-table">
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th>Đơn giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th></th>
        </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
        <tr>
            <th colspan="3">Tổng cộng</th>
            <th id="cart-total"></th>
            <th></th>
        </tr>
    </tfoot>
</table>

<form action="<?= url('checkout.php') ?>" method="post" id="checkout-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="cart_json" id="cart-json" value="">
    <p><button type="submit" class="btn btn-accent" id="checkout-button">Đặt hàng</button></p>
</form>

<script>
window.MINISHOP_PRODUCTS = <?= json_encode(
    $products,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
) ?>;
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
