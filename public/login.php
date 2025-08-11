<?php
// vuln_login.php -- intentionally vulnerable (LAB ONLY)

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

require 'config/config.php'; // $conn mysqli connection

$error = '';

// Không giới hạn số lần đăng nhập, không captcha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Không kiểm tra input, cho phép input cực lớn (DoS)
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        // SQL Injection (string concat, không escape)
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $res = $conn->query($sql);

        if ($res === false) {
            // Tiết lộ chi tiết lỗi DB và query
            $error = "DB error: " . $conn->error . " -- Query: " . $sql;
        } else {
            if ($res->num_rows === 0) {
                // Tiết lộ user không tồn tại
                $error = "Tài khoản không tồn tại.";
            } else {
                $user = $res->fetch_assoc();

                // So sánh mật khẩu plaintext (no hashing)
                if ($password === $user['password']) {
                    $_SESSION['user'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['uid']  = $user['id'];

                    // Cookie chứa plaintext credential, không Secure/HttpOnly
                    setcookie(
                        'auth_user',
                        $user['id'] . ':' . $user['username'] . ':' . $user['password'],
                        time() + 60 * 60 * 24 * 30,
                        "/"
                    );

                    // Cho phép đăng nhập cùng lúc trên nhiều thiết bị
                    header('Location: index.php');
                    exit;
                } else {
                    // Phân biệt sai mật khẩu
                    $error = "Sai mật khẩu.";
                }
            }
        }
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
</body>

</html>