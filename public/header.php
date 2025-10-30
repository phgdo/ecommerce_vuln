<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper: build a lab auth cookie from current session if no real cookie exists.
 * Returns base64(json) or null.
 */
function build_auth_cookie_from_session_for_header(): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) return null;
    if (!isset($_SESSION['uid']) || !isset($_SESSION['user'])) return null;
    $role_numeric = 0;
    if (isset($_SESSION['role']) && intval($_SESSION['role']) === 1) $role_numeric = 1;
    $auth_obj = [
        'id' => intval($_SESSION['uid']),
        'username' => (string)($_SESSION['user'] ?? ''),
        'password' => '', // blank for header construction (lab)
        'role' => $role_numeric
    ];
    $json = json_encode($auth_obj);
    if ($json === false) return null;
    return base64_encode($json);
}

// Determine admin link target: include cookie and sid if available
$admin_href = '/admin.php';
$params = [];

// Prefer existing auth cookie if present
if (!empty($_COOKIE['auth'])) {
    $params['cookie'] = $_COOKIE['auth'];
} else {
    // fallback: try to build from session
    $fromSess = build_auth_cookie_from_session_for_header();
    if ($fromSess !== null) $params['cookie'] = $fromSess;
}

// Include session id (if session active)
$sid = session_id();
if ($sid) {
    $params['sid'] = $sid;
}

if (!empty($params)) {
    $admin_href .= '?' . http_build_query($params);
}

// Determine whether user is admin
$isAdmin = (!empty($_SESSION['role']) && intval($_SESSION['role']) === 1);
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
            <?php
            // Always render the admin link element.
            // If user is admin -> visible and navigable.
            // If not admin -> keep element but hide it (hidden, aria-hidden, tabindex) to follow your request.
            $aAttrs = [];
            if ($isAdmin) {
                $aAttrs['href'] = $admin_href;
                $aAttrs['class'] = 'admin-dashboard';
                // visible: do nothing else
            } else {
                // still include href (optional), but mark as hidden
                $aAttrs['href'] = $admin_href;
                $aAttrs['class'] = 'admin-dashboard visually-hidden';
                $aAttrs['hidden'] = 'hidden';
                $aAttrs['aria-hidden'] = 'true';
                $aAttrs['tabindex'] = '-1';
            }

            // Build attribute string safely
            $attrStr = '';
            foreach ($aAttrs as $k => $v) {
                $attrStr .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
            }
            ?>
            <a<?= $attrStr ?>>⚙️ Admin Dashboard</a>

                <a href="update_profile.php" class="user-icon-link">
                    <span class="user-icon">👤
                        <?php
                        if (function_exists('xss_ouput')) {
                            echo xss_ouput((string)($_SESSION['user'] ?? ''));
                        } else {
                            echo htmlspecialchars($_SESSION['user'] ?? '');
                        }
                        ?>
                    </span>
                </a>
                <a href="cart.php" class="cart-icon">🛒</a>
                <a href="logout.php" class="btn-link logout">Đăng xuất</a>
            <?php endif; ?>
    </div>

    <style>
        /* helper: visually-hidden class in case 'hidden' is not styled uniformly */
        .visually-hidden {
            position: absolute !important;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</header>