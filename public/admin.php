<?php
// admin.php auth prelude (safe flow to add ?cookie then validate, avoids redirect loops)

// Start session (allow session fixation earlier if you need: session_id($_GET['sid']) before this)
if (session_status() === PHP_SESSION_NONE) {
    if (isset($_GET['sid'])) {
        session_id($_GET['sid']); // lab-only optional
    }
    session_start();
}

// Load helper functions (must exist)
$func_path = __DIR__ . '/functions.php';
if (!file_exists($func_path)) {
    // try alternate path
    $func_path = __DIR__ . '/includes/functions.php';
}
if (!file_exists($func_path)) {
    // cannot proceed: helpers missing
    include __DIR__ . '/header.php';
    echo '<div style="max-width:900px;margin:40px auto;font-family:system-ui,Segoe UI,Roboto,Arial">';
    echo '<h2>Access denied</h2>';
    echo '<p>Required helper file not found. Check server configuration.</p>';
    echo '</div>';
    include __DIR__ . '/footer.php';
    exit;
}
require_once $func_path;

// Small safe wrapper for auth_decode (returns null on error)
function safe_auth_decode()
{
    if (!function_exists('auth_decode')) return null;
    try {
        $a = auth_decode();
        if (!is_array($a)) return null;
        return $a;
    } catch (Throwable $e) {
        error_log("safe_auth_decode error: " . $e->getMessage());
        return null;
    }
}

// If cookie param present -> set cookie and validate it
if (isset($_GET['cookie'])) {
    $cookie_val = $_GET['cookie'];

    // Set auth cookie (lab convenience; not HttpOnly/Secure intentionally)
    setcookie('auth', $cookie_val, time() + 60 * 60 * 24 * 30, '/');
    $_COOKIE['auth'] = $cookie_val;

    // Validate cookie
    $auth = safe_auth_decode();
    $is_admin = is_array($auth) && isset($auth['role']) && intval($auth['role']) === 1;

    if ($is_admin) {
        // promote session from cookie
        $_SESSION['user'] = $auth['username'] ?? '';
        $_SESSION['role'] = intval($auth['role']);
        $_SESSION['uid']  = isset($auth['id']) ? intval($auth['id']) : 0;

        // Redirect to clean URL (remove cookie param). This is one-time redirect.
        $url = strtok($_SERVER['REQUEST_URI'], '?'); // path only
        header('Location: ' . $url);
        exit;
    } else {
        // cookie present but not admin -> deny
        include __DIR__ . '/header.php';
        echo '<div style="max-width:900px;margin:40px auto;font-family:system-ui,Segoe UI,Roboto,Arial">';
        echo '<h2>Access denied</h2>';
        echo '<p>Provided cookie does not grant admin privileges.</p>';
        echo '</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

// No cookie param: check session first
if (isset($_SESSION['role']) && intval($_SESSION['role']) === 1) {
    // already admin via session -> continue to dashboard content
    // nothing to do here
} else {
    // Not admin in session: if there is an auth cookie available, redirect to add it as ?cookie=...
    if (isset($_COOKIE['auth']) && $_COOKIE['auth'] !== '') {
        // Redirect to /admin.php?cookie=<cookie_value>
        // We redirect only when no cookie param present, so one redirect will occur.
        $qs = $_GET;
        $qs['cookie'] = $_COOKIE['auth'];
        $loc = $_SERVER['PHP_SELF'] . '?' . http_build_query($qs);
        header('Location: ' . $loc);
        exit;
    }

    // No cookie in client and no session -> deny
    include __DIR__ . '/header.php';
    echo '<div style="max-width:900px;margin:40px auto;font-family:system-ui,Segoe UI,Roboto,Arial">';
    echo '<h2>Access denied</h2>';
    echo '<p>No cookie or session to authenticate admin. For lab: visit <code>/admin.php?cookie=BASE64_JSON</code></p>';
    echo '</div>';
    include __DIR__ . '/footer.php';
    exit;
}



// If admin, show dashboard (the original content)
// include header
$header_path = __DIR__ . '/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    echo '<header><h1>NNshop - Admin</h1></header>';
}

// Dummy business data for phones (you can change these numbers)
$phone_sales = [
    ['model' => 'iPhone 16 Pro Max', 'jan' => 120, 'feb' => 150, 'mar' => 180],
    ['model' => 'Samsung S25 Ultra',  'jan' => 80,  'feb' => 95,  'mar' => 110],
    ['model' => 'Samsung S25',   'jan' => 60,  'feb' => 75,  'mar' => 90],
    ['model' => 'Google Pixel 10', 'jan' => 40,  'feb' => 55,  'mar' => 70],
    ['model' => 'iPad Pro M4', 'jan' => 30,  'feb' => 45,  'mar' => 60],
];

// Aggregate monthly totals
$months = ['Jan', 'Feb', 'Mar'];
$monthly_totals = [
    array_sum(array_column($phone_sales, 'jan')),
    array_sum(array_column($phone_sales, 'feb')),
    array_sum(array_column($phone_sales, 'mar'))
];

// Prepare data for chart (labels + datasets)
$labels = array_map(function ($p) {
    return $p['model'];
}, $phone_sales);

// For Chart.js we will show stacked bar: each month is a dataset
$dataset_jan = array_map(function ($p) {
    return $p['jan'];
}, $phone_sales);
$dataset_feb = array_map(function ($p) {
    return $p['feb'];
}, $phone_sales);
$dataset_mar = array_map(function ($p) {
    return $p['mar'];
}, $phone_sales);

?>
<link rel="stylesheet" href="assets/css/login_register.css">
<link rel="stylesheet" href="assets/css/product.css">
<link rel="stylesheet" href="assets/css/style.css">
<div class="admin-container" style="max-width:1100px;margin:20px auto;font-family:system-ui,Segoe UI,Roboto,Arial;">
    <h2>Admin Dashboard</h2>

    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px">
        <div style="flex:0 0 240px;padding:12px;border:1px solid #e5e7eb;border-radius:8px;background:#fff">
            <h3 style="margin:6px 0">Logs</h3>
            <p class="small" style="color:#555">Xem file access logs</p>
            <a href="/admin/admin_view_logs.php" style="display:inline-block;margin-top:8px;padding:8px 12px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none">View Logs</a>
        </div>

        <div style="flex:1;padding:12px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;min-width:300px">
            <h3 style="margin:6px 0">Monthly Sales Overview (Phones)</h3>
            <p class="small" style="color:#555">Tổng lượt bán từng tháng</p>
            <div style="display:flex;gap:12px;margin-top:8px">
                <?php foreach ($months as $i => $m): ?>
                    <div style="flex:1;padding:8px;border-radius:6px;background:#f8fafc;text-align:center">
                        <div style="font-size:20px;font-weight:600"><?= htmlspecialchars($monthly_totals[$i]) ?></div>
                        <div class="small" style="color:#666"><?= htmlspecialchars($m) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <section style="background:#fff;padding:12px;border:1px solid #e5e7eb;border-radius:8px">
        <h3>Phone Models Sales (by month)</h3>
        <canvas id="phonesChart" width="900" height="380" style="max-width:100%"></canvas>
        <p class="small" style="color:#666;margin-top:8px">Data is dummy sample for demo purposes.</p>

        <h4 style="margin-top:18px">Details</h4>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f3f4f6">
                    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb">Model</th>
                    <th style="text-align:right;padding:8px;border:1px solid #e5e7eb">Jan</th>
                    <th style="text-align:right;padding:8px;border:1px solid #e5e7eb">Feb</th>
                    <th style="text-align:right;padding:8px;border:1px solid #e5e7eb">Mar</th>
                    <th style="text-align:right;padding:8px;border:1px solid #e5e7eb">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($phone_sales as $p):
                    $total = $p['jan'] + $p['feb'] + $p['mar'];
                ?>
                    <tr>
                        <td style="padding:8px;border:1px solid #e5e7eb"><?= htmlspecialchars($p['model']) ?></td>
                        <td style="padding:8px;border:1px solid #e5e7eb;text-align:right"><?= intval($p['jan']) ?></td>
                        <td style="padding:8px;border:1px solid #e5e7eb;text-align:right"><?= intval($p['feb']) ?></td>
                        <td style="padding:8px;border:1px solid #e5e7eb;text-align:right"><?= intval($p['mar']) ?></td>
                        <td style="padding:8px;border:1px solid #e5e7eb;text-align:right;font-weight:600"><?= intval($total) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    (() => {
        const labels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const data = {
            labels: labels,
            datasets: [{
                    label: 'Jan',
                    data: <?= json_encode($dataset_jan) ?>,
                },
                {
                    label: 'Feb',
                    data: <?= json_encode($dataset_feb) ?>,
                },
                {
                    label: 'Mar',
                    data: <?= json_encode($dataset_mar) ?>,
                }
            ]
        };
        const cfg = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        stacked: false
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };
        const ctx = document.getElementById('phonesChart').getContext('2d');
        new Chart(ctx, cfg);
    })();
</script>

<?php
// include footer if exists
$footer_path = __DIR__ . '/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo '<footer style="text-align:center;margin:30px 0;color:#777">NNshop &copy; ' . date('Y') . '</footer>';
}
