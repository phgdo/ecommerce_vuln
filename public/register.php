<?php
// vuln_register.php  -- intentionally vulnerable (LAB ONLY)

// Cho phép attacker fix session ID từ URL trước khi start
if (isset($_GET['sid'])) {
    session_id($_GET['sid']);
}

// Cấu hình session insecure trước khi start
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30); // 30 ngày
session_set_cookie_params(60 * 60 * 24 * 30);

// Bắt đầu session (insecure)
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // KHÔNG validate độ dài, ký tự đặc biệt, email...
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '' || $confirm === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        if ($password !== $confirm) {
            $error = "Mật khẩu nhập lại không khớp.";
        } else {
            // VULN 1: SQL Injection khi insert
            // Không escape, không prepared statement
            // Lưu plaintext password
            $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
            if ($conn->query($sql) === TRUE) {
                // Lấy id mới tạo
                $uid = $conn->insert_id;

                // VULN 2: Set cookie chứa plaintext credential
                setcookie('auth_user', $uid . ':' . $username . ':' . $password, time() + 60 * 60 * 24 * 30, "/");

                // VULN 3: Không regenerate session -> Session fixation
                $_SESSION['user'] = $username;
                $_SESSION['uid'] = $uid;

                // VULN 4: Second Order SQL Injection
                // Giả sử username được lưu và sau đó dùng trực tiếp trong câu lệnh SQL khác ở nơi khác
                // Ví dụ: Sau đăng ký, lưu vào bảng profile nhưng concat trực tiếp
                $bio = "Welcome " . $username; // Attacker có thể chèn SQL tại đây, sẽ nổ ở một query khác
                $conn->query("INSERT INTO profiles (user_id, bio) VALUES ($uid, '$bio')");

                $success = "Đăng ký thành công! <a href='login.php'>Đăng nhập ngay</a>";
            } else {
                // VULN 5: Tiết lộ lỗi DB + Query cho user
                $error = "DB error: " . $conn->error . " -- Query: " . $sql;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="assets/css/login_register.css">
</head>

<body>
    <a href="index.php" class="back-home">← Quay lại trang chủ</a>
    <div class="page-container">

        <div class="form-container">
            <h2>Đăng ký</h2>
            <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
            <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Tên đăng nhập">
                <input type="password" name="password" placeholder="Mật khẩu">
                <input type="password" name="confirm" placeholder="Nhập lại mật khẩu">
                <button type="submit">Đăng ký</button>
            </form>
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>
    </div>
</body>

</html>