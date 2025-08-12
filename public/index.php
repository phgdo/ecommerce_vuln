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
    <?php include 'header.php'; ?>
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
    <?php include 'footer.php'; ?>
</body>

</html>