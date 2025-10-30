<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['uid'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để xem giỏ hàng.";
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['uid']);

// Lấy giỏ hàng của user
$stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
if (!$stmt->fetch()) {
    $cart_id = null;
}
$stmt->close();

$cart_items = [];
$total_price = 0;

if ($cart_id) {
    $sql = "SELECT cp.id AS cart_product_id, p.id AS product_id, p.name, p.price, p.discount_price, cp.quantity, p.remainingquantity,
            (SELECT image_url FROM product_images WHERE product_id = p.id LIMIT 1) AS image_url
            FROM cart_product cp
            JOIN products p ON cp.productid = p.id
            WHERE cp.cartid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['effective_price'] = ($row['discount_price'] > 0) ? $row['discount_price'] : $row['price'];
        $row['subtotal'] = $row['effective_price'] * $row['quantity'];
        $total_price += $row['subtotal'];
        $cart_items[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f4f4f4;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-cell img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .btn {
            padding: 6px 12px;
            background: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn-danger {
            background: #dc3545;
        }

        .cart-total {
            text-align: right;
            font-size: 1.2em;
            margin-top: 10px;
        }

        .cart-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="cart-container">
        <h1>🛒 Giỏ hàng của bạn</h1>

        <?php if (empty($cart_items)): ?>
            <p>Giỏ hàng của bạn đang trống. <a href="products.php">Mua ngay</a></p>
        <?php else: ?>
            <form method="post" action="update_cart.php">
                <table>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Còn trong kho</th>
                            <th>Thành tiền</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td class="product-cell">
                                    <a href="product_detail.php?id=<?= $item['product_id'] ?>">
                                        <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/no-image.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    </a>
                                    <a href="product_detail.php?id=<?= $item['product_id'] ?>">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                </td>
                                <td><?= number_format($item['effective_price'], 2, ',', '.') ?>$</td>
                                <td>
                                    <input type="number" name="qty[<?= $item['cart_product_id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['remainingquantity'] ?>">
                                </td>
                                <td><?= $item['remainingquantity'] ?></td>
                                <td><?= number_format($item['subtotal'], 2, ',', '.') ?>$</td>
                                <td>
                                    <a class="btn btn-danger" href="remove_from_cart.php?id=<?= $item['cart_product_id'] ?>" onclick="return confirm('Xóa sản phẩm này?')">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="cart-total"><strong>Tổng cộng:</strong> <?= number_format($total_price, 2, ',', '.') ?>$</p>

                <div class="cart-actions">
                    <button type="submit" class="btn">Cập nhật giỏ hàng</button>
                    <a href="checkout.php" class="btn">Thanh toán</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>