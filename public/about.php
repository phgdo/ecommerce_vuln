<?php
session_start();
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Giới thiệu - E-Commerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .about-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }

        .about-container h1 {
            font-size: 28px;
            margin-bottom: 16px;
        }

        .about-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .team {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .team-member {
            text-align: center;
        }

        .team-member img {
            width: 100%;
            max-width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 8px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="about-container">
            <div class="about-section">
                <h1>Về Chúng Tôi</h1>
                <p>Chào mừng bạn đến với <strong>E-Commerce Shop</strong> – nền tảng mua sắm trực tuyến đáng tin cậy, mang đến cho bạn trải nghiệm mua sắm dễ dàng, nhanh chóng và an toàn.</p>
                <p>Chúng tôi cam kết mang lại các sản phẩm chất lượng cao với mức giá hợp lý, cùng với dịch vụ chăm sóc khách hàng tận tâm.</p>
            </div>

            <div class="about-section">
                <h2>Sứ mệnh</h2>
                <p>Sứ mệnh của chúng tôi là giúp mọi người tiếp cận sản phẩm chất lượng, từ thời trang, điện tử, gia dụng đến thực phẩm, một cách nhanh chóng và tiện lợi nhất.</p>
            </div>

            <div class="about-section">
                <h2>Đội Ngũ</h2>
                <div class="team">
                    <div class="team-member">
                        <img src="assets/avatar/elonmusk.webp" alt="Thành viên 1">
                        <div>Elon Musk</div>
                        <small>CEO & Founder</small>
                    </div>
                    <div class="team-member">
                        <img src="assets/avatar/billgates.jpg" alt="Thành viên 2">
                        <div>Bill Gates</div>
                        <small>Quản lý sản phẩm</small>
                    </div>
                    <div class="team-member">
                        <img src="assets/avatar/samaltman.jpg" alt="Thành viên 3">
                        <div>Sam Altman</div>
                        <small>Trưởng phòng kỹ thuật</small>
                    </div>
                </div>
            </div>

            <div class="about-section">
                <h2>Liên hệ</h2>
                <p>Email: contact@ecommerce.com</p>
                <p>Hotline: 0123 456 789</p>
                <p>Địa chỉ: 123 Đường ABC, Quận XYZ, TP.HCM</p>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>

</html>