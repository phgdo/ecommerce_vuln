<?php
session_start();

// Xử lý khi gửi form
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error_message = "Vui lòng điền đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ.";
    } else {
        // Ở đây bạn có thể gửi email hoặc lưu vào DB
        // mail($email_to, "Liên hệ từ $name", $message);
        $success_message = "Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm.";
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Liên hệ - E-Commerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .contact-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .contact-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .contact-info p {
            margin: 6px 0;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .contact-form button {
            background: #28a745;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .contact-form button:hover {
            background: #218838;
        }

        .alert {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="contact-container">
            <div class="contact-section">
                <h1>Liên hệ với chúng tôi</h1>
                <div class="contact-info">
                    <p><strong>Địa chỉ:</strong> 123 Đường ABC, Quận XYZ, TP.HCM</p>
                    <p><strong>Email:</strong> contact@ecommerce.com</p>
                    <p><strong>Hotline:</strong> 0123 456 789</p>
                </div>
            </div>

            <div class="contact-section">
                <h2>Gửi tin nhắn</h2>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php elseif ($error_message): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <form method="POST" class="contact-form">
                    <input type="text" name="name" placeholder="Họ và tên" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <textarea name="message" placeholder="Nội dung tin nhắn..." rows="5"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <button type="submit">Gửi</button>
                </form>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>

</html>