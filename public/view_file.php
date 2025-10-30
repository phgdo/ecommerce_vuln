<?php
// public/view_file.php
// LAB ONLY - intentionally dangerous
// - Accepts ?file=...
// - If .php -> include() (executed) and show output
// - If .pdf -> stream with Content-Type: application/pdf (inline) BEFORE any HTML output
// - If image types -> display image tag
// - Else -> show text content inside <pre>
// WARNING: DO NOT USE IN PRODUCTION. This allows LFI/RCE and arbitrary file read.

ini_set('display_errors', 0);
error_reporting(E_ALL);

$requested = $_GET['file'] ?? '';
$error = '';
$info = '';
$displayMode = ''; // 'include', 'pdf', 'image', 'text', 'download'
$outputBuffer = null;
$path = null;

if ($requested === '') {
    // show help below in HTML
} else {
    // Normalize: trim spaces
    $path = trim($requested);

    // Disallow remote URLs
    if (preg_match('#^https?://#i', $path)) {
        $error = "Remote URL not supported. Provide a local path.";
    } else {
        // attempt to resolve real path (may fail for non-existent file)
        $rp = @realpath($path);
        // we build $info but DON'T echo it yet (HTML later)
        $info .= "Requested path: " . htmlspecialchars($path) . "<br>";
        $info .= "Real path: " . ($rp === false ? '<em>N/A (file may not exist)</em>' : htmlspecialchars($rp)) . "<br>";

        if (!file_exists($path)) {
            $error = "File không tồn tại: " . htmlspecialchars($path);
        } elseif (!is_readable($path)) {
            $error = "Không thể đọc file (permission denied): " . htmlspecialchars($path);
        } else {
            // determine extension
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = @mime_content_type($path) ?: '';

            // decide mode
            if ($ext === 'php') {
                // include and capture output
                $displayMode = 'include';
                ob_start();
                // NOTE: include executes code -> lab-only
                try {
                    include $path;
                } catch (Throwable $e) {
                    // include may throw fatal errors in PHP 7+; capture safely
                    echo "[INCLUDE ERROR] " . htmlspecialchars($e->getMessage());
                }
                $outputBuffer = ob_get_clean();
            } elseif ($ext === 'pdf') {
                $displayMode = 'pdf';
            } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
                $displayMode = 'image';
            } else {
                // treat as text (but could be binary) - we will check mime for safety
                if (strpos($mime, 'text/') === 0 || $mime === 'application/json' || $mime === 'application/xml') {
                    $displayMode = 'text';
                } else {
                    // unknown binary type -> treat as text fallback
                    $displayMode = 'text';
                }
            }
        }
    }
}

// If PDF -> stream and exit BEFORE sending any HTML
if ($displayMode === 'pdf' && $path !== null) {
    $rp = @realpath($path);
    if ($rp !== false && is_readable($rp)) {
        // Send PDF headers and stream file, then exit
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($rp) . '"');
        header('Content-Length: ' . filesize($rp));
        // optional caching headers (short)
        header('Cache-Control: private, max-age=600, must-revalidate');
        readfile($rp);
        exit;
    } else {
        // fallback to error and render HTML below
        $error = "Không thể stream PDF file.";
        $displayMode = ''; // ensure HTML flow
    }
}

// Helper: build URL for an existing local file relative to web root.
function file_url_from_path($path)
{
    if (function_exists('getcwd')) {
        $cwd = realpath(getcwd()); // project root when running under apache in many setups
        $rp = @realpath($path);
        if ($rp !== false && $cwd !== false && strpos($rp, $cwd) === 0) {
            $rel = substr($rp, strlen($cwd));
            if ($rel === '' || $rel[0] !== '/') $rel = '/' . $rel;
            return $rel;
        }
    }
    return null;
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>View File (lab)</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: system-ui, Segoe UI, Roboto, Arial;
            margin: 20px;
            background: #fff;
            color: #111;
        }

        .box {
            max-width: 1100px;
            margin: 0 auto;
        }

        input[type=text] {
            width: 80%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            padding: 8px 12px;
            margin-left: 6px;
        }

        pre {
            white-space: pre-wrap;
            word-break: break-all;
            background: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .note {
            font-size: 0.9em;
            color: #555;
            margin-top: 8px;
        }

        .warn {
            background: #fff1f2;
            border-left: 4px solid #ef4444;
            padding: 8px;
            margin: 12px 0;
        }

        .meta {
            font-size: 0.9em;
            color: #334155;
            margin-top: 8px;
        }

        img.preview {
            max-width: 100%;
            height: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-top: 8px;
        }

        .pdf-embed {
            width: 100%;
            height: 800px;
            border: 1px solid #e5e7eb;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1>View File</h1>

        <form method="get" action="view_file.php">
            <input type="text" name="file" placeholder="e.g. public/temp.log or /etc/passwd or views/template.php" value="<?= htmlspecialchars($requested) ?>">
            <button type="submit">View</button>
        </form>

        <?php if ($error): ?>
            <div class="warn"><strong>Error:</strong> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($info)): ?>
            <div class="meta"><?= $info ?></div>
        <?php endif; ?>

        <?php if ($displayMode === 'include'): ?>
            <h2>Included PHP Output</h2>
            <div class="meta">(Output captured from <code><?= htmlspecialchars($path) ?></code>)</div>
            <div style="background:#fff; padding:12px; border:1px solid #e5e7eb; border-radius:6px; margin-top:8px;">
                <?= $outputBuffer === null ? '<em>(no output)</em>' : $outputBuffer ?>
            </div>

            <?php elseif ($displayMode === 'image'):
            $url = file_url_from_path($path);
            if ($url !== null): ?>
                <h2>Image Preview</h2>
                <img class="preview" src="<?= htmlspecialchars($url) ?>" alt="image preview">
                <div class="meta">Source: <?= htmlspecialchars($path) ?></div>
                <?php else:
                $rp = @realpath($path);
                if ($rp !== false && is_readable($rp)) {
                    $data = base64_encode(file_get_contents($rp));
                    $mime = mime_content_type($rp) ?: 'application/octet-stream';
                ?>
                    <h2>Image Preview (embedded)</h2>
                    <img class="preview" src="data:<?= htmlspecialchars($mime) ?>;base64,<?= $data ?>" alt="embedded image">
                    <div class="meta">Source: <?= htmlspecialchars($path) ?></div>
                <?php } else { ?>
                    <div class="warn">Không thể hiển thị image.</div>
                <?php } ?>
            <?php endif; ?>

            <?php elseif ($displayMode === 'text'):
            $rp = @realpath($path);
            if ($rp !== false && is_readable($rp)):
                $txt = @file_get_contents($rp);
                if ($txt === false) {
                    echo '<div class="warn">Không thể đọc file.</div>';
                } else { ?>
                    <h2>File content</h2>
                    <pre><?= htmlspecialchars($txt) ?></pre>
                    <div class="meta">Source: <?= htmlspecialchars($path) ?></div>
                <?php }
            else: ?>
                <div class="warn">Không thể đọc file.</div>
            <?php endif; ?>

        <?php elseif ($requested !== ''): ?>
            <div class="warn">Không thể xử lý file này hoặc file không hợp lệ.</div>
        <?php endif; ?>

        <hr>
    </div>
</body>

</html>