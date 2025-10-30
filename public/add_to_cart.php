<?php
session_start();
require 'config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['uid'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để thêm vào giỏ hàng.";
    header("Location: login.php");
    exit;
}

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

$user_id   = intval($_SESSION['uid']);
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$qty        = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

// Validate dữ liệu
if ($product_id <= 0 || $qty <= 0) {
    $_SESSION['error'] = "Dữ liệu sản phẩm không hợp lệ.";
    header("Location: products.php");
    exit;
}

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT id, name, price, discount_price, remainingquantity FROM products WHERE id = ? AND status = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error'] = "Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.";
    header("Location: products.php");
    exit;
}

// Kiểm tra tồn kho
if ($qty > $product['remainingquantity']) {
    $_SESSION['error'] = "Số lượng đặt vượt quá tồn kho.";
    header("Location: products.php");
    exit;
}

// 1️⃣ Lấy hoặc tạo giỏ hàng cho user
$stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
if (!$stmt->fetch()) {
    $stmt->close();
    // Chưa có giỏ → tạo mới
    $stmt_insert = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $stmt_insert->bind_param("i", $user_id);
    $stmt_insert->execute();
    $cart_id = $stmt_insert->insert_id;
    $stmt_insert->close();
} else {
    $stmt->close();
}

// 2️⃣ Kiểm tra sản phẩm đã có trong giỏ chưa
$stmt = $conn->prepare("SELECT id, quantity FROM cart_product WHERE cartid = ? AND productid = ?");
$stmt->bind_param("ii", $cart_id, $product_id);
$stmt->execute();
$stmt->bind_result($cart_product_id, $current_qty);

if ($stmt->fetch()) {
    // Nếu đã có → cập nhật số lượng
    $stmt->close();
    $new_qty = $current_qty + $qty;
    $stmt_update = $conn->prepare("UPDATE cart_product SET quantity = ? WHERE id = ?");
    $stmt_update->bind_param("ii", $new_qty, $cart_product_id);
    $stmt_update->execute();
    $stmt_update->close();
} else {
    $stmt->close();
    // Nếu chưa có → thêm mới
    $stmt_insert = $conn->prepare("INSERT INTO cart_product (cartid, productid, quantity, price, discount_price) VALUES (?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("iiidd", $cart_id, $product_id, $qty, $product['price'], $product['discount_price']);
    $stmt_insert->execute();
    $stmt_insert->close();
}

$_SESSION['success'] = "Đã thêm sản phẩm vào giỏ hàng.";
header("Location: cart.php");
exit;
