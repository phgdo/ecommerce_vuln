<?php
// public/register.php
// Form đăng ký với kiểm tra maxlength phía client, không kiểm tra phía server (vuln A)

// Cho phép attacker fix session ID từ URL trước khi start
if (isset($_GET['sid'])) {
    session_id($_GET['sid']);
}
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(60 * 60 * 24 * 30);
session_start();

// Include functions.php trong cùng thư mục
$func = __DIR__ . '/functions.php';
if (!file_exists($func)) {
    error_log("Missing include: $func");
    http_response_code(500);
    echo "Internal server error. Please contact admin.";
    exit;
}
require_once $func;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = register_user($_POST);
    if ($res['ok']) {
        $success = $res['msg'] . " <a href='login.php'>Đăng nhập ngay</a>";
        $_SESSION['uid'] = $res['uid'];
        $_SESSION['user'] = $_POST['username'] ?? '';
    } else {
        $error = $res['msg'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="assets/css/login_register.css">
    <script>
        // Client-side validation: chỉ giới hạn maxlength=10 cho 'name'
        function validateForm() {
            var name = document.getElementById('name').value || '';
            if (name.length > 10) {
                alert('Name không được quá 10 ký tự (client-side check).');
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <a href="index.php" class="back-home">← Quay lại trang chủ</a>
    <div class="page-container">
        <div class="form-container">
            <h2>Đăng ký</h2>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

            <form method="POST" onsubmit="return validateForm();">
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <input type="password" name="confirm" placeholder="Nhập lại mật khẩu" required>
                <input type="text" id="name" name="name" placeholder="Tên (tối đa 10 ký tự)" maxlength="10">
                <button type="submit">Đăng ký</button>
            </form>
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>
    </div>
</body>

</html>