<?php
// public/login_debug.php — tạm thời bật hiển thị lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>DEBUG login environment</h2>";

// Check files
$func = __DIR__ . '/functions.php';
$config = __DIR__ . '/config/config.php';

echo "<p>functions.php exists: " . (file_exists($func) ? 'YES' : 'NO') . "</p>";
echo "<p>config.php exists: " . (file_exists($config) ? 'YES' : 'NO') . "</p>";

if (!file_exists($func)) {
    exit("<p style='color:red'>Missing functions.php — fix path</p>");
}

// try include functions.php
try {
    require_once $func;
    echo "<p>Included functions.php OK</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>Include functions.php failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// check login_user function
echo "<p>function login_user exists: " . (function_exists('login_user') ? 'YES' : 'NO') . "</p>";
echo "<p>function register_user exists: " . (function_exists('register_user') ? 'YES' : 'NO') . "</p>";

// Check DB connection variable if available
global $conn;
if (isset($conn)) {
    echo "<p>\$conn is set. Type: " . gettype($conn) . "</p>";
    if ($conn instanceof mysqli) {
        echo "<p>mysqli connection: connect_errno=" . $conn->connect_errno . ", host_info=" . htmlspecialchars($conn->host_info) . "</p>";
    } else {
        var_dump($conn);
    }
} else {
    echo "<p style='color:orange'>\$conn not set after include — config not loaded or config path wrong</p>";
}

// show last 50 lines of php_warnings.log if exists
$logfile = __DIR__ . '/php_warnings.log';
if (file_exists($logfile)) {
    echo "<h3>php_warnings.log (tail)</h3><pre>";
    $lines = file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $last = array_slice($lines, -50);
    echo htmlspecialchars(implode("\n", $last));
    echo "</pre>";
} else {
    echo "<p>php_warnings.log not found at $logfile</p>";
}

echo "<p>Done.</p>";
