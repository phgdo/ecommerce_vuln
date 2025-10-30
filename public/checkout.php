<?php
session_start();
require 'config/config.php';

// 1️⃣ Kiểm tra đăng nhập
if (!isset($_SESSION['uid'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để thanh toán.";
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['uid']);

// 2️⃣ Lấy giỏ hàng của user
$stmt = $conn->prepare("
    SELECT cp.productid, p.name, cp.quantity, cp.price, cp.discount_price
    FROM cart_product cp
    JOIN cart c ON cp.cartid = c.id
    JOIN products p ON cp.productid = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cart_items)) {
    $_SESSION['error'] = "Giỏ hàng của bạn đang trống.";
    header("Location: cart.php");
    exit;
}

// 3️⃣ Nếu submit form thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $total = 0;

    foreach ($cart_items as $item) {
        $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
        $total += $price * $item['quantity'];
    }

    // Tạo đơn hàng
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("ids", $user_id, $total, $payment_method);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Thêm sản phẩm vào order_items
    foreach ($cart_items as $item) {
        $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $item['productid'], $item['quantity'], $price);
        $stmt->execute();
        $stmt->close();
    }

    // Xóa giỏ hàng sau khi đặt
    $stmt = $conn->prepare("DELETE cp FROM cart_product cp JOIN cart c ON cp.cartid = c.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "Đặt hàng thành công! Mã đơn: #{$order_id}";
    header("Location: orders.php"); // Trang xem đơn hàng
    exit;
}

function money($v)
{
    return number_format($v, 2, ',', '.') . 'đ';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        th {
            background: #f5f5f5;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Thanh toán</h1>

        <table>
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>SL</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; ?>
                <?php foreach ($cart_items as $item): ?>
                    <?php
                    $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
                    $subtotal = $price * $item['quantity'];
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= money($price) ?></td>
                        <td><?= money($subtotal) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="3">Tổng cộng</th>
                    <th><?= money($total) ?></th>
                </tr>
            </tbody>
        </table>

        <form method="post" style="margin-top:20px;">
            <h3>Phương thức thanh toán (demo)</h3>
            <label><input type="radio" name="payment_method" value="cod" checked> Thanh toán khi nhận hàng</label><br>
            <label><input type="radio" name="payment_method" value="momo"> Ví MoMo (demo)</label><br>
            <label><input type="radio" name="payment_method" value="bank"> Chuyển khoản ngân hàng (demo)</label><br><br>

            <button type="submit" style="padding:10px 20px; background:#28a745; color:#fff; border:none; cursor:pointer;">
                Xác nhận đặt hàng
            </button>
        </form>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>