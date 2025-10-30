<?php
// public/debug_auth.php
// Debug tool for vuln C (auth cookie = base64(json))
// INTENTIONALLY for local lab use only.

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// include functions if available (we use auth_decode() and is_admin_via_cookie())
$func = __DIR__ . '/functions.php';
if (file_exists($func)) {
    require_once $func;
} else {
    $func_missing = true;
}

// Handle form submissions: set cookie, tamper to admin, delete
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_cookie') {
        $id = $_POST['id'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = isset($_POST['role']) ? intval($_POST['role']) : 0;
        if ($role !== 1) $role = 0; // normalize to 0 or 1

        $auth_obj = [
            'id' => $id,
            'username' => $username,
            'password' => $password,
            'role' => $role
        ];
        $val = base64_encode(json_encode($auth_obj));
        // set cookie (lab: not HttpOnly/Secure)
        setcookie('auth', $val, time() + 60 * 60 * 24 * 30, "/");
        // also update $_COOKIE for immediate display
        $_COOKIE['auth'] = $val;
        $notice = "Đã set cookie 'auth'.";
        // redirect to avoid resubmit
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'make_admin') {
        // decode existing cookie (or create minimal if none)
        $cur = $_COOKIE['auth'] ?? null;
        $data = null;
        if ($cur) {
            $dec = base64_decode($cur, true);
            $data = json_decode($dec, true);
        }
        if (!is_array($data)) {
            $data = ['id' => '1', 'username' => 'user', 'password' => 'pass', 'role' => 0];
        }
        $data['role'] = 1;
        $val = base64_encode(json_encode($data));
        setcookie('auth', $val, time() + 60 * 60 * 24 * 30, "/");
        $_COOKIE['auth'] = $val;
        $notice = "Đã sửa cookie: role => 1 (admin)";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_cookie') {
        setcookie('auth', '', time() - 3600, "/");
        unset($_COOKIE['auth']);
        $notice = "Đã xóa cookie 'auth'.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Helper to pretty print JSON safely
function pretty_json($j)
{
    if ($j === null) return '<em>NULL</em>';
    $s = json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return '<pre>' . htmlspecialchars($s) . '</pre>';
}

?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Debug Auth Cookie (Vuln C)</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: system-ui, Segoe UI, Roboto, Arial;
            margin: 20px;
            background: #f7fafc;
            color: #111
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06)
        }

        h1 {
            margin-top: 0
        }

        label {
            display: block;
            margin: 8px 0 4px
        }

        input[type=text],
        input[type=password],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            margin-top: 10px;
            border-radius: 6px;
            border: 0;
            background: #2563eb;
            color: #fff;
            cursor: pointer
        }

        .btn.warn {
            background: #d97706
        }

        .notice {
            background: #e6fffa;
            padding: 8px;
            border-left: 4px solid #10b981;
            margin-bottom: 10px
        }

        .small {
            font-size: 0.9em;
            color: #555
        }

        .box {
            background: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Debug Auth Cookie (Vuln C)</h1>
        <p class="small">Công cụ debug cho lab. Hiển thị cookie <code>auth</code>, decode, session. Cho phép set/ sửa/ xóa cookie.</p>

        <?php if (!empty($notice)): ?>
            <div class="notice"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <h2>Raw cookie</h2>
        <div class="box"><strong>auth (raw):</strong>
            <div style="word-break:break-all;margin-top:6px"><?= isset($_COOKIE['auth']) ? htmlspecialchars($_COOKIE['auth']) : '<em>Không có cookie auth</em>' ?></div>
        </div>

        <h2>Decoded cookie (base64 -> json)</h2>
        <div class="box">
            <?php
            $decoded = null;
            if (isset($_COOKIE['auth'])) {
                $dec = base64_decode($_COOKIE['auth'], true);
                if ($dec !== false) {
                    $decoded = json_decode($dec, true);
                }
            }
            echo pretty_json($decoded);
            ?>
        </div>

        <h2>auth_decode() & is_admin_via_cookie()</h2>
        <div class="box">
            <?php if (isset($func_missing) && $func_missing): ?>
                <div class="small">functions.php không tồn tại hoặc chưa include — các hàm tiện ích không khả dụng.</div>
            <?php else: ?>
                <div><strong>auth_decode() returned:</strong>
                    <?= pretty_json(function_exists('auth_decode') ? auth_decode() : null) ?></div>

                <div style="margin-top:8px"><strong>is_admin_via_cookie():</strong>
                    <span class="small"><?= function_exists('is_admin_via_cookie') ? (is_admin_via_cookie() ? '<strong style="color:green">TRUE</strong>' : '<strong style="color:red">FALSE</strong>') : 'n/a' ?></span>
                </div>
            <?php endif; ?>
        </div>

        <h2>Session (PHP session)</h2>
        <div class="box">
            <pre><?= htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>

        <h2>Set / Edit cookie</h2>
        <div class="box">
            <form method="post">
                <input type="hidden" name="action" value="set_cookie">
                <label>ID</label>
                <input type="text" name="id" value="<?= isset($decoded['id']) ? htmlspecialchars($decoded['id']) : '1' ?>">
                <label>Username</label>
                <input type="text" name="username" value="<?= isset($decoded['username']) ? htmlspecialchars($decoded['username']) : 'user' ?>">
                <label>Password</label>
                <input type="text" name="password" value="<?= isset($decoded['password']) ? htmlspecialchars($decoded['password']) : 'pass' ?>">
                <label>Role (0 = user, 1 = admin)</label>
                <select name="role">
                    <option value="0" <?= (isset($decoded['role']) && intval($decoded['role']) === 0) ? 'selected' : '' ?>>0 (user)</option>
                    <option value="1" <?= (isset($decoded['role']) && intval($decoded['role']) === 1) ? 'selected' : '' ?>>1 (admin)</option>
                </select>
                <button class="btn" type="submit">Set cookie (base64(json))</button>
            </form>

            <form method="post" style="display:inline-block;margin-left:8px">
                <input type="hidden" name="action" value="make_admin">
                <button class="btn warn" type="submit">Quick: make role = 1 (admin)</button>
            </form>

            <form method="post" style="display:inline-block;margin-left:8px">
                <input type="hidden" name="action" value="delete_cookie">
                <button class="btn" type="submit" onclick="return confirm('Xóa cookie auth?')">Delete cookie</button>
            </form>
        </div>

        <hr>
        <p class="small">Warning: Đây là công cụ cho lab. Việc sửa cookie để leo privilege chỉ nên thực hiện trong môi trường huấn luyện. Xóa file này khi kết thúc lab.</p>
    </div>
</body>

</html>