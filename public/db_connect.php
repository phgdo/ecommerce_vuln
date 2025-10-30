<?php
// public/db_connect.php
// Simple MySQL connection tester — chỉ dùng cho lab hoặc development.

ini_set('display_errors', 0);
error_reporting(E_ALL);

$host   = $_POST['host']   ?? '127.0.0.1';
$port   = $_POST['port']   ?? '3306';
$user   = $_POST['user']   ?? '';
$pass   = $_POST['pass']   ?? '';
$dbname = $_POST['dbname'] ?? ''; // optional

$connected = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim($user) === '') {
        $message = "Vui lòng nhập username.";
    } else {
        // Kết nối không bắt buộc database
        $mysqli = @new mysqli($host, $user, $pass, '', (int)$port);

        if ($mysqli->connect_errno) {
            // Hiển thị lỗi đơn giản để tránh lộ thông tin nhạy cảm
            $message = "Kết nối thất bại. Vui lòng kiểm tra lại thông tin đăng nhập.";
        } else {
            $connected = true;
            $msg = "Đã kết nối MySQL thành công tới {$host}:{$port} với user '" . htmlspecialchars($user) . "'.";
            if ($dbname !== '') {
                if (@$mysqli->select_db($dbname)) {
                    $msg .= " Đã chọn database <code>" . htmlspecialchars($dbname) . "</code>.";
                } else {
                    $msg .= " Không thể chọn database <code>" . htmlspecialchars($dbname) . "</code>.";
                }
            }
            $message = $msg;
            $mysqli->close();
        }
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>DB Connect Tester</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: system-ui, Segoe UI, Roboto, Arial;
            margin: 20px;
            background: #fff;
            color: #111;
        }

        .box {
            max-width: 680px;
            margin: 0 auto;
            padding: 16px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 8px;
        }

        input[type=text],
        input[type=password] {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
        }

        button {
            margin-top: 12px;
            padding: 8px 12px;
        }

        .ok {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 8px;
            margin-top: 12px;
        }

        .err {
            background: #fff1f2;
            border-left: 4px solid #ef4444;
            padding: 8px;
            margin-top: 12px;
        }

        .small {
            font-size: 0.9em;
            color: #666;
            margin-top: 6px;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>DB Connect Tester</h2>
        <p class="small">Nhập thông tin MySQL (database là tùy chọn). Nếu để trống, script chỉ kiểm tra kết nối tài khoản.</p>

        <?php if ($message): ?>
            <div class="<?= $connected ? 'ok' : 'err' ?>">
                <?= $connected ? '<strong>Success:</strong> ' : '<strong>Error:</strong> ' ?>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <label>Host</label>
            <input type="text" name="host" value="<?= htmlspecialchars($host) ?>" placeholder="db">

            <label>Port</label>
            <input type="text" name="port" value="<?= htmlspecialchars($port) ?>" placeholder="3306">

            <label>Username</label>
            <input type="text" name="user" value="<?= htmlspecialchars($user) ?>" required>

            <label>Password</label>
            <input type="password" name="pass" value="<?= htmlspecialchars($pass) ?>">

            <label>Database (tùy chọn)</label>
            <input type="text" name="dbname" value="<?= htmlspecialchars($dbname) ?>" placeholder="vuln_ecom">

            <button type="submit">Thử kết nối</button>
        </form>

        <hr>
        <p class="small">⚠️ Lưu ý: chỉ dùng trong môi trường thử nghiệm. Không hiển thị lỗi chi tiết hoặc thông tin đăng nhập trên production.</p>
    </div>
</body>

</html>