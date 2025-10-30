<?php
// public/functions.php
// Các hàm chung cho app (INTENTIONALLY VULNERABLE - LAB ONLY)

// Load config (kết nối DB mysqli)
$config_path = __DIR__ . '/config/config.php';
if (!file_exists($config_path)) {
    error_log("Missing config file: $config_path");
    http_response_code(500);
    echo "Internal server error. Please contact admin.";
    exit;
}
require_once $config_path;

/* ---------------------------
   Các hàm đăng ký / đăng nhập
   (đã có trước, giữ nguyên)
   --------------------------- */

/**
 * register_user(...)
 * (vulnerable A)...
 */

function sanitize_input($data)
{
    // Loại bỏ khoảng trắng thừa
    $data = trim($data);
    // Xóa ký tự NULL byte hoặc control characters
    $data = preg_replace('/[\x00-\x1F\x7F]/u', '', $data);
    // Mã hóa các ký tự đặc biệt để tránh XSS
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $data;
}

// Hàm kiểm tra username hợp lệ (chỉ cho phép chữ, số, dấu gạch dưới)
function validate_username($username)
{
    return preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username);
}

// Hàm kiểm tra password độ dài và ký tự
function validate_password($password)
{
    // Tối thiểu 8 ký tự, có ít nhất 1 chữ và 1 số
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+=\-]{8,}$/', $password);
}

// Hàm kiểm tra tên người dùng (chỉ chấp nhận ký tự chữ và khoảng trắng)
function validate_name($name)
{
    return preg_match('/^[\p{L}\s\'\-]{1,100}$/u', $name);
}

function register_user(array $data)
{
    global $conn;

    $username = sanitize_input($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $confirm  = $data['confirm'] ?? '';
    $name     = sanitize_input($data['name'] ?? '');

    if ($username === '' || $password === '' || $confirm === '') {
        return ['ok' => false, 'msg' => 'Vui lòng nhập đầy đủ thông tin.', 'uid' => null];
    }

    if (!validate_username($username)) {
        return ['ok' => false, 'msg' => 'Tên đăng nhập không hợp lệ.', 'uid' => null];
    }

    if (!validate_password($password)) {
        return ['ok' => false, 'msg' => 'Mật khẩu phải dài ít nhất 8 ký tự và có cả chữ và số.', 'uid' => null];
    }

    if ($password !== $confirm) {
        return ['ok' => false, 'msg' => 'Mật khẩu nhập lại không khớp.', 'uid' => null];
    }

    if (!validate_name($name)) {
        return ['ok' => false, 'msg' => 'Tên không hợp lệ.', 'uid' => null];
    }

    // ✅ Sử dụng prepared statement để tránh SQL Injection
    $stmt = $conn->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
    $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $username, $hashed_pass, $name);

    if ($stmt->execute()) {
        $uid = $conn->insert_id;
        $bio = "Hello, " . $name;

        $stmt2 = $conn->prepare("INSERT INTO profiles (user_id, bio) VALUES (?, ?)");
        $stmt2->bind_param("is", $uid, $bio);
        $stmt2->execute();

        // ⚠ Không nên lưu password trong cookie!
        $token = bin2hex(random_bytes(16));
        setcookie('auth_user', $uid . ':' . $token, time() + 60 * 60 * 24 * 30, "/", "", true, true);

        return ['ok' => true, 'msg' => 'Đăng ký thành công!', 'uid' => $uid];
    } else {
        return ['ok' => false, 'msg' => 'DB error: ' . $stmt->error, 'uid' => null];
    }
}

/**
 * login_user(...)
 * (vulnerable C)...
 */
function login_user(array $data)
{
    global $conn;

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($username === '' || $password === '') {
        return ['ok' => false, 'msg' => 'Vui lòng nhập đầy đủ thông tin.', 'user' => null];
    }

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $res = $conn->query($sql);

    if ($res === false) {
        return ['ok' => false, 'msg' => 'DB error: ' . $conn->error . ' -- Query: ' . $sql, 'user' => null];
    }

    if ($res->num_rows === 0) {
        return ['ok' => false, 'msg' => 'Tài khoản không tồn tại.', 'user' => null];
    }

    $user = $res->fetch_assoc();

    if ($password !== $user['password']) {
        return ['ok' => false, 'msg' => 'Sai mật khẩu.', 'user' => null];
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['uid']  = $user['id'];
    }

    $role_numeric = 0;
    if (isset($user['role']) && ($user['role'] === 'admin' || $user['role'] === '1' || $user['role'] === 1)) {
        $role_numeric = 1;
    }

    $auth_obj = [
        'id' => $user['id'],
        'username' => $user['username'],
        'password' => $user['password'],
        'role' => $role_numeric
    ];
    $cookie_value = base64_encode(json_encode($auth_obj));
    setcookie('auth', $cookie_value, time() + 60 * 60 * 24 * 30, "/");

    return ['ok' => true, 'msg' => 'Đăng nhập thành công.', 'user' => $user];
}

/**
 * auth_decode(), is_admin_via_cookie()
 */
function auth_decode()
{
    if (!isset($_COOKIE['auth'])) {
        return null;
    }
    $decoded = base64_decode($_COOKIE['auth'], true);
    if ($decoded === false) return null;
    $data = json_decode($decoded, true);
    if (!is_array($data)) return null;
    return $data;
}

function is_admin_via_cookie()
{
    $auth = auth_decode();
    if ($auth && isset($auth['role']) && intval($auth['role']) === 1) {
        return true;
    }
    return false;
}

/* ---------------------------
   BỔ SUNG: Image utilities (được cung cấp nhưng có thể KHÔNG được gọi)
   --------------------------- */

/**
 * is_allowed_file
 * - Kiểm tra extension và kích thước
 * - Trả false nếu không hợp lệ
 */
function is_allowed_file(array $file, array $opts = [])
{
    // opts: max_size (bytes), allowed_ext (array)
    $max_size = $opts['max_size'] ?? 2 * 1024 * 1024; // 2MB mặc định
    $allowed_ext = $opts['allowed_ext'] ?? ['jpg', 'jpeg', 'png', 'gif'];

    if (!isset($file['name']) || !isset($file['size']) || !isset($file['tmp_name'])) {
        return false;
    }

    if ($file['size'] > $max_size) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) return false;

    // Additional MIME type check (basic)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $mime_allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $mime_allowed, true)) return false;

    return true;
}

/**
 * sanitize_filename
 * - Thay ký tự lạ, cắt độ dài tên file
 */
function sanitize_filename(string $name)
{
    // giữ lại chữ, số, dấu -, _, .
    $base = preg_replace('/[^a-zA-Z0-9\-\._]/', '_', $name);
    // tránh tên quá dài
    if (strlen($base) > 100) {
        $base = substr($base, 0, 100);
    }
    return $base;
}

/**
 * store_avatar_safe
 * - Hàm an toàn để lưu avatar: kiểm tra bằng is_allowed_file + sanitize_filename + move file
 * - Trả đường dẫn tương đối nếu lưu thành công, false nếu thất bại
 */
function store_avatar_safe(array $file, int $uid)
{
    $uploads_dir = __DIR__ . '/uploads';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    if (!is_allowed_file($file)) {
        return false;
    }

    $orig = sanitize_filename($file['name']);
    $newname = time() . '_' . bin2hex(random_bytes(6)) . '_' . $orig;
    $dest = $uploads_dir . '/' . $newname;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        // Optionally update DB: users.avatar = 'uploads/...'
        global $conn;
        $rel = 'uploads/' . $newname;
        $conn->query("UPDATE users SET avatar = '" . $conn->real_escape_string($rel) . "' WHERE id = " . intval($uid));
        return $rel;
    }
    return false;
}

function xss_ouput(string $s): string
{
    // remove <script> blocks (case-insensitive)
    $s = preg_replace('#<\s*script\b[^>]*>(.*?)<\s*/\s*script\s*>#is', '', $s);

    // naive attempt to strip "javascript:" in attributes (very weak)
    $s = preg_replace('#javascript:#i', '', $s);

    // Return remaining string RAW (no htmlentities, no escaping of < > or quotes)
    return $s;
}

/* End of file functions.php */
