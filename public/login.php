<?php
// public/login.php  -- intentionally vulnerable (LAB ONLY)

// Ẩn warning trên giao diện, nhưng vẫn log ra file
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_warnings.log');

// SESSION FIXATION: cho phép set session id qua GET (insecure)
if (isset($_GET['sid'])) {
    session_id($_GET['sid']);
}

// Session timeout cực dài (30 ngày) -> insecure
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(60 * 60 * 24 * 30);

// Bắt đầu session (không regenerate ID) -> insecure
session_start();

// Include functions.php (chứa login_user() với vuln C)
$func_path = __DIR__ . '/functions.php';
if (!file_exists($func_path)) {
    error_log("Missing functions include: $func_path");
    http_response_code(500);
    echo "Internal server error.";
    exit;
}
require_once $func_path;

$error = '';

// Xử lý POST bằng login_user() trong functions.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gọi hàm login_user (vulnerable) — hàm tự thực hiện SQL query và set cookie 'auth'
    $res = login_user($_POST);

    if ($res['ok']) {
        // login_user đã set cookie 'auth' và session; chuyển hướng về index
        header('Location: index.php');
        exit;
    } else {
        // Hiện thông báo lỗi (đã sanitized bằng htmlspecialchars khi echo ra)
        $error = $res['msg'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="assets/css/login_register.css">
</head>

<body>
    <a href="index.php" class="back-home">← Quay lại trang chủ</a>
    <div class="page-container">
        <div class="form-container">
            <h2>Đăng nhập</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="text" name="username" placeholder="Tên đăng nhập">
                <input type="password" name="password" placeholder="Mật khẩu">
                <button type="submit">Đăng nhập</button>
            </form>

            <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
        </div>
    </div>
</body>

</html>