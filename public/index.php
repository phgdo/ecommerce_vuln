<?php
session_start();

// Giả lập người dùng đã đăng nhập (để test)
$is_logged_in = isset($_SESSION['username']);
$username = $is_logged_in ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>NNshop - Trang chủ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script defer src="assets/js/script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="index.php" class="logo">NNshop</a>
        <nav>
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Shop</a>
            <a href="#">Contact</a>
        </nav>
        <div class="header-right">
            <?php if (!isset($_SESSION['user'])): ?>
                <button id="loginBtn">Đăng nhập</button>
                <button id="registerBtn">Đăng ký</button>
            <?php else: ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="admin-dashboard">Admin Dashboard</a>
                <?php endif; ?>
                <div class="user-icon">👤</div>
                <div class="cart-icon">🛒</div>
            <?php endif; ?>
        </div>
    </header>

    <!-- BANNER -->
    <!-- BANNER -->
    <section class="banner">
        <img id="bannerImage" src="assets/resources/banner1.jpg" alt="NNshop Banner">
    </section>

    <!-- MAIN -->
    <main>
        <h1>Chào mừng đến với NNshop!</h1>
        <p>Mua điện thoại, máy tính chính hãng với giá tốt nhất.</p>
    </main>

    <!-- FOOTER -->
    <footer>
        <p>&copy; <?= date("Y") ?> NNshop. All rights reserved.</p>
    </footer>
</body>

</html>