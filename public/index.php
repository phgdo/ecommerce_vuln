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
        <div class="logo"><a href="index.php">NNshop</a></div>
        <nav>
            <ul class="nav-links">
                <li><a href="#">Home</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Shop</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </nav>
        <div class="icons">
            <a href="#"><i class="fas fa-shopping-cart"></i></a>
            <div class="user-dropdown">
                <i class="fas fa-user" id="userIcon"></i>
                <div class="dropdown-content" id="userDropdown">
                    <?php if (!$is_logged_in): ?>
                        <form method="POST" action="login.php">
                            <input type="text" name="username" placeholder="Username">
                            <input type="password" name="password" placeholder="Password">
                            <button type="submit">Login</button>
                            <a href="register.php">Register</a>
                        </form>
                    <?php else: ?>
                        <p>Hello, <strong><?= htmlspecialchars($username) ?></strong></p>
                        <a href="change_password.php">Change Password</a>
                        <a href="logout.php">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
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