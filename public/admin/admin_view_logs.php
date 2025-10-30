<?php
// public/admin/admin_view_logs.php
// INTENTIONALLY VULNERABLE - LAB ONLY
// - Không kiểm tra session/login (unauthen)
// - Đọc file public/apache_access.log (file nằm trong folder public/)
// - In raw log lines (KHÔNG escape) -> vulnerable to log injection / XSS
//
// WARNING: chỉ dùng trong môi trường lab nội bộ. Không deploy ra production.

ini_set('display_errors', 0);
error_reporting(E_ALL);

// admin file is located at .../public/admin/
// public/apache_access.log is at .../public/apache_access.log
// So compute path as parent dir of __DIR__ then 'apache_access.log'
$default_logfile = dirname(__DIR__) . '/apache_access.log';

// allow an override via GET param 'file' (intentional for lab)
// but by default use the correct path inside public/
$logfile = $default_logfile;
if (isset($_GET['file']) && $_GET['file'] !== '') {
    // Intentionally permissive for lab (DO NOT do this in production)
    $logfile = $_GET['file'];
}

// lines to tail (default 200)
$n = isset($_GET['n']) ? intval($_GET['n']) : 200;
if ($n <= 0) $n = 200;

// quick helper: tail last N lines without reading entire huge file
function tail_file_lines($filepath, $lines = 200)
{
    $result = [];
    if (!is_readable($filepath)) return $result;
    $fp = fopen($filepath, 'r');
    if (!$fp) return $result;
    $buffer = '';
    $pos = -1;
    fseek($fp, 0, SEEK_END);
    $filesize = ftell($fp);
    $linecount = 0;
    while ($linecount < $lines && abs($pos) < $filesize) {
        fseek($fp, $pos, SEEK_END);
        $char = fgetc($fp);
        if ($char === "\n") {
            if ($buffer !== '') {
                $result[] = strrev($buffer);
                $buffer = '';
                $linecount++;
            }
        } else {
            $buffer .= $char;
        }
        $pos--;
    }
    if ($buffer !== '' && $linecount < $lines) {
        $result[] = strrev($buffer);
    }
    fclose($fp);
    return array_reverse($result);
}

$exists = file_exists($logfile);
$readable = is_readable($logfile);
$lines = [];
if ($exists && $readable) {
    $lines = tail_file_lines($logfile, $n);
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Admin View Logs (VULNERABLE)</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: system-ui, Segoe UI, Roboto, Arial;
            margin: 20px;
            background: #fff;
            color: #111
        }

        .container {
            max-width: 1100px;
            margin: 0 auto
        }

        pre {
            white-space: pre-wrap;
            word-break: break-all;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e5e7eb
        }

        .warn {
            background: #fff1f2;
            border-left: 4px solid #ef4444;
            padding: 8px;
            margin-bottom: 12px
        }

        .note {
            font-size: 0.9em;
            color: #555
        }

        .meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Admin View Logs (VULNERABLE)</h1>
        <!-- <div class="warn">
            <strong>Lab notice:</strong> trang này <em>KHÔNG</em> kiểm tra quyền (unauthen) và in log raw. Chỉ dùng trong lab.
        </div> -->

        <div class="meta">
            <strong>Log file:</strong> <?= htmlspecialchars($logfile) ?> &nbsp; | &nbsp;
            <strong>Readable:</strong> <?= $exists ? ($readable ? 'Yes' : 'No (permission denied)') : 'No (not found)' ?> &nbsp; | &nbsp;
            <strong>Tail lines:</strong> <?= intval($n) ?>
        </div>

        <?php if (!$exists): ?>
            <div class="warn">Log file không tồn tại: <?= htmlspecialchars($logfile) ?></div>
            <p class="note">Tạo file <code>public/apache_access.log</code> trong folder public/ hoặc mount/copy file log vào vị trí này để demo.</p>
        <?php elseif (!$readable): ?>
            <div class="warn">Không thể đọc file log (permission denied): <?= htmlspecialchars($logfile) ?></div>
            <p class="note">Trong môi trường lab bạn có thể copy log vào project hoặc điều chỉnh quyền tệp để cho phép đọc.</p>
        <?php else: ?>
            <h2>Last <?= intval($n) ?> lines (raw)</h2>
            <pre>
<?php
            // !!! INTENTIONALLY RAW: không escape nội dung log -> vulnerable to XSS
            foreach ($lines as $ln) {
                echo $ln . "\n";
            }
?>
      </pre>

            <p class="note">Bạn có thể thay đổi số dòng bằng param <code>?n=100</code>. Param <code>?file=</code> cho phép override đường dẫn (mục đích lab).</p>
        <?php endif; ?>

        <hr>
        <p class="note">Reminder: sau khi demo, xoá/khóa file này hoặc thêm kiểm tra quyền và escape output trước khi dùng trên môi trường thật.</p>
    </div>
</body>

</html>