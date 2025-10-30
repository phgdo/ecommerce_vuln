<?php
// public/update_profile.php
// Trang cập nhật profile + upload avatar + update name
// Handler upload intentionally insecure (KHÔNG gọi store_avatar_safe())
// Name update: client-side maxlength only, backend DOES NOT validate -> vuln A

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING);

session_start();

// include functions (để có $conn, auth helpers nhưng NOT dùng avatar helpers)
$func = __DIR__ . '/functions.php';
if (!file_exists($func)) {
    error_log("Missing functions include: $func");
    http_response_code(500);
    echo "Internal server error.";
    exit;
}
require_once $func;

// Basic auth: require session user (lab)
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = intval($_SESSION['uid']);
$error = '';
$success = '';

// HANDLE NAME UPDATE (VULN A)
// - Client-side may enforce maxlength=10, backend DOES NOT check length or sanitize
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name_submit'])) {
    $newname = $_POST['name'] ?? '';
    // Intentionally no validation/escaping -> vuln A
    $conn->query("UPDATE users SET name = '$newname' WHERE id = $uid");
    // Also update profiles table (second-order possibility)
    $conn->query("UPDATE profiles SET bio = 'Hello, $newname' WHERE user_id = $uid");
    $success = "Cập nhật tên thành công.";
}

// INSECURE UPLOAD HANDLER (vulnerable B):
// - Không kiểm tra extension/mime-type/size
// - Không sanitize filename
// - Lưu thẳng vào /public/uploads/
// - Cho phép overwrite (nếu trùng tên), cho phép .php upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && isset($_POST['avatar_submit'])) {
    $f = $_FILES['avatar'];
    // intentionally NOT using is_allowed_file() nor sanitize_filename()
    $uploads_dir = __DIR__ . '/uploads';
    if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);

    // Use original filename directly (vuln)
    $dest = $uploads_dir . '/' . $f['name'];

    // Move uploaded file without checks
    if (move_uploaded_file($f['tmp_name'], $dest)) {
        // Save path to DB (no escaping to simulate inconsistent handling)
        $rel = 'uploads/' . $f['name'];
        $conn->query("UPDATE users SET avatar = '$rel' WHERE id = $uid");
        $success = "Uploaded avatar: " . htmlspecialchars($rel);
    } else {
        $error = "Upload failed (move_uploaded_file returned false).";
    }
}

// Load current user info
$user = null;
$res = $conn->query("SELECT id, username, name, avatar FROM users WHERE id = $uid LIMIT 1");
if ($res && $res->num_rows === 1) $user = $res->fetch_assoc();

?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Update profile</title>
    <link rel="stylesheet" href="assets/css/login_register.css">
    <script>
        // Client-side validation: tên tối đa 10 ký tự (only client-side)
        function validateNameForm() {
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
    <a href="index.php" class="back-home">← Quay lại</a>
    <div class="page-container">
        <div class="form-container">
            <h2>Update profile</h2>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

            <?php if ($user): ?>
                <p>Username: <?= htmlspecialchars($user['username']) ?></p>
                <p>Name: <?= htmlspecialchars($user['name']) ?></p>
                <p>Current avatar:
                    <?php if (!empty($user['avatar'])): ?>
                        <br><img src="<?= htmlspecialchars($user['avatar']) ?>" alt="avatar" style="max-width:150px">
                    <?php else: ?>
                        <em>No avatar</em>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <!-- FORM cập nhật tên (client-side maxlength only) -->
            <form method="post" onsubmit="return validateNameForm();">
                <label>Cập nhật tên (tối đa 10 ký tự trên client)</label>
                <input type="text" id="name" name="name" placeholder="Tên mới" maxlength="10" value="<?= isset($user['name']) ? htmlspecialchars($user['name']) : '' ?>">
                <button type="submit" name="name_submit">Cập nhật tên</button>
            </form>

            <hr>

            <!-- FORM upload avatar (INSECURE handler used) -->
            <form method="post" enctype="multipart/form-data">
                <label>Upload avatar (INSECURE handler used)</label>
                <input type="file" name="avatar">
                <button type="submit" name="avatar_submit">Upload</button>
            </form>

            <p class="small">Note: Handler upload ở trang này KHÔNG dùng các hàm kiểm tra có sẵn trong functions.php — mục đích lab là mô phỏng inconsistent validation (vuln B). Tên được giới hạn chỉ bằng client-side maxlength (vuln A) — backend không kiểm tra.</p>
        </div>
    </div>
</body>

</html>