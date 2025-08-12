<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xử lý logout nếu có ?action=logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<header>
    <a href="index.php" class="logo">NNshop</a>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="products.php">Products</a>
        <a href="contact.php">Contact</a>
    </nav>
    <div class="header-right">
        <?php if (!isset($_SESSION['user'])): ?>
            <a href="login.php" class="btn-link">Đăng nhập</a>
            <a href="register.php" class="btn-link">Đăng ký</a>
        <?php else: ?>
            <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="admin-dashboard">Admin Dashboard</a>
            <?php endif; ?>
            <span class="user-icon">👤 <?= htmlspecialchars($_SESSION['user']) ?></span>
            <a href="cart.php" class="cart-icon">🛒</a>
            <a href="?action=logout" class="btn-link logout">Đăng xuất</a>
        <?php endif; ?>
    </div>
</header>