<?php
// product_detail.php (VULNERABLE LAB)
session_start();
require 'config/config.php'; // $conn = mysqli connection

$id = isset($_GET['id']) ? $_GET['id'] : 0; // no validation -> SQL Injection
$sql = "SELECT * FROM products WHERE id = $id"; // vulnerable
$res = $conn->query($sql);
if (!$res) {
    die("SQL Error: " . $conn->error . " -- Query: " . $sql);
}
$product = $res->fetch_assoc();
if (!$product) {
    die("Không tìm thấy sản phẩm.");
}

// Lấy ảnh
$imgs_res = $conn->query("SELECT * FROM product_images WHERE product_id = $id");
$images = $imgs_res ? $imgs_res->fetch_all(MYSQLI_ASSOC) : [];

// Lấy cấu hình
$specs_res = $conn->query("SELECT * FROM product_specs WHERE product_id = $id");
$specs = $specs_res ? $specs_res->fetch_all(MYSQLI_ASSOC) : [];

// Lấy đánh giá
$sort_reviews = isset($_GET['sort_reviews']) ? $_GET['sort_reviews'] : 'date';
$rev_res = $conn->query("SELECT * FROM reviews WHERE product_id = $id ORDER BY $sort_reviews DESC"); // sort injection
$reviews = $rev_res ? $rev_res->fetch_all(MYSQLI_ASSOC) : [];

// Lấy bình luận
$cmt_res = $conn->query("SELECT * FROM comments WHERE product_id = $id ORDER BY created_at DESC");
$comments = $cmt_res ? $cmt_res->fetch_all(MYSQLI_ASSOC) : [];

// Lấy sản phẩm liên quan
$cat_id = $product['category_id'];
$rel_res = $conn->query("SELECT id, name, price, image_path FROM products WHERE category_id = $cat_id AND id <> $id LIMIT 4");
$related = $rel_res ? $rel_res->fetch_all(MYSQLI_ASSOC) : [];

function money($v)
{
    return number_format($v, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title><?= $product['name'] ?> - NNshop</title>
    <link rel="stylesheet" href="assets/css/product.css">
    <script>
        let currentSlide = 0;

        function showSlide(idx) {
            const slides = document.querySelectorAll('.slider img');
            if (idx < 0) idx = slides.length - 1;
            if (idx >= slides.length) idx = 0;
            slides.forEach(s => s.style.display = 'none');
            slides[idx].style.display = 'block';
            currentSlide = idx;
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }
        window.onload = function() {
            showSlide(0);
        };
    </script>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1><?= $product['name'] ?></h1> <!-- Stored XSS -->

        <!-- Slider -->
        <div class="slider">
            <?php foreach ($images as $img): ?>
                <img src="<?= $img['image_path'] ?>" alt="Ảnh sản phẩm">
            <?php endforeach; ?>
            <button class="prev" onclick="prevSlide()">❮</button>
            <button class="next" onclick="nextSlide()">❯</button>
        </div>

        <!-- Giá -->
        <div class="price-box">
            <span class="old-price"><?= money($product['price']) ?></span>
            <span class="sale-price"><?= money($product['sale_price']) ?></span>
        </div>

        <!-- Cấu hình -->
        <h2>Cấu hình sản phẩm</h2>
        <table class="specs-table">
            <?php foreach ($specs as $s): ?>
                <tr>
                    <th><?= $s['spec_name'] ?></th>
                    <td><?= $s['spec_value'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Mô tả -->
        <h2>Mô tả sản phẩm</h2>
        <div class="description"><?= $product['description'] ?></div>

        <!-- Đánh giá -->
        <h2>Đánh giá</h2>
        <form method="get">
            <input type="hidden" name="id" value="<?= $id ?>">
            <select name="sort_reviews">
                <option value="date">Ngày</option>
                <option value="rating">Điểm</option>
            </select>
            <button type="submit">Sắp xếp</button>
        </form>
        <?php foreach ($reviews as $r): ?>
            <div class="review">
                <strong><?= $r['username'] ?></strong> - <?= $r['rating'] ?>/5<br>
                <?= $r['comment'] ?>
            </div>
        <?php endforeach; ?>

        <!-- Bình luận -->
        <h2>Bình luận</h2>
        <form method="post" action="comment.php">
            <input type="hidden" name="product_id" value="<?= $id ?>">
            <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? 1 ?>">
            <textarea name="comment" rows="3"></textarea><br>
            <button type="submit">Gửi</button>
        </form>
        <?php foreach ($comments as $c): ?>
            <div class="comment">
                <strong><?= $c['username'] ?></strong>: <?= $c['content'] ?>
            </div>
        <?php endforeach; ?>

        <!-- Sản phẩm liên quan -->
        <h2>Sản phẩm tương tự</h2>
        <div class="related">
            <?php foreach ($related as $rp): ?>
                <div class="card">
                    <img src="<?= $rp['image_path'] ?>" alt="<?= $rp['name'] ?>">
                    <h3><?= $rp['name'] ?></h3>
                    <p><?= money($rp['price']) ?></p>
                    <a href="product_detail.php?id=<?= $rp['id'] ?>">Xem</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>

</body>

</html>