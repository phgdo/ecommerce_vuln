<?php
session_start();
require 'config/config.php'; // $conn = mysqli connection

// Kiểm tra id sản phẩm
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ID sản phẩm không hợp lệ.");
}

// Lấy thông tin sản phẩm (Prepared Statement)
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
if (!$product) {
    die("Không tìm thấy sản phẩm.");
}

// Lấy ảnh
$stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy cấu hình
$stmt = $conn->prepare("SELECT configuration FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc()['configuration'];

// Lấy đánh giá
$allowed_sorts = ['created_at', 'rating'];
$sort_reviews = isset($_GET['sort_reviews']) && in_array($_GET['sort_reviews'], $allowed_sorts)
    ? $_GET['sort_reviews']
    : 'created_at';

$sql = "
    SELECT u.username, pr.rating, c.comment, pr.created_at
    FROM product_reviews pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN comments c ON pr.user_id = c.user_id AND pr.product_id = c.productid
    WHERE pr.product_id = ?
    ORDER BY $sort_reviews DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error); // debug nếu query sai
}
$stmt->bind_param("i", $id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$sql = "
    SELECT p.id, p.name, p.price, pi.image_url
    FROM products p
    LEFT JOIN product_images pi 
        ON p.id = pi.product_id
    WHERE p.categoryid = ? AND p.id <> ?
    GROUP BY p.id
    LIMIT 4
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $product['categoryid'], $id);
$stmt->execute();
$related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function money($v)
{
    return number_format($v, 2, ',', '.') . '$';
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($product['name']) ?> - NNshop</title>
    <link rel="stylesheet" href="assets/css/product.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-detail {
            max-width: 1200px;
            margin: auto;
        }

        .top-section {
            display: flex;
            gap: 20px;
        }

        .left-info {
            flex: 1;
        }

        .right-images {
            flex: 1;
        }

        .slider {
            position: relative;
            width: 100%;
            height: 400px;
            /* Cố định chiều cao để ảnh không kéo giãn layout */
            overflow: hidden;
        }

        .slider img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* Giữ nguyên tỉ lệ ảnh */
            background-color: #fff;
        }

        .price-box {
            margin: 10px 0;
        }

        .old-price {
            text-decoration: line-through;
            color: gray;
            margin-right: 10px;
        }

        .sale-price {
            color: red;
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>
    <script>
        let currentSlide = 0;

        function showSlide(idx) {
            const slides = document.querySelectorAll('.slider img');
            if (slides.length === 0) return;
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

    <div class="container product-detail">

        <div class="top-section">
            <!-- Cột trái -->
            <div class="left-info">
                <h1><?= htmlspecialchars($product['name']) ?></h1>

                <!-- Giá -->
                <div class="price-box">
                    <?php if ($product['price'] > 0): ?>
                        <span class="old-price"><?= money($product['price']) ?></span>
                    <?php endif; ?>
                    <span class="sale-price"><?= money($product['discount_price']) ?></span>
                </div>

                <!-- Cấu hình -->
                <?php if (!empty($product['configuration'])): ?>
                    <h2>Cấu hình sản phẩm</h2>
                    <table class="specs-table">
                        <?php
                        // Giả sử configuration lưu dạng "CPU: Intel Core i5\nRAM: 8GB\nSSD: 256GB"
                        $lines = preg_split('/\r\n|\r|\n/', $product['configuration']);
                        foreach ($lines as $line) {
                            if (strpos($line, ':') !== false) {
                                list($spec_name, $spec_value) = explode(':', $line, 2);
                                echo '<tr>
                        <th>' . htmlspecialchars(trim($spec_name)) . '</th>
                        <td>' . htmlspecialchars(trim($spec_value)) . '</td>
                      </tr>';
                            } else {
                                // Nếu không có dấu ":" thì hiển thị nguyên dòng
                                echo '<tr>
                        <th colspan="2">' . htmlspecialchars(trim($line)) . '</th>
                      </tr>';
                            }
                        }
                        ?>
                    </table>
                <?php endif; ?>

                <!-- Nút thêm vào giỏ hàng -->
                <form method="post" action="add_to_cart.php" style="margin-top:15px;">
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <input type="number" name="qty" value="1" min="1" max="<?= $product['remainingquantity'] ?>">
                    <button type="submit" style="padding:10px 15px;background:#28a745;color:#fff;border:none;cursor:pointer;">
                        🛒 Thêm vào giỏ hàng
                    </button>
                </form>
            </div>

            <!-- Cột phải -->
            <div class="right-images">
                <div class="slider">
                    <?php if (!empty($images)): ?>
                        <?php foreach ($images as $img): ?>
                            <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Ảnh sản phẩm">
                        <?php endforeach; ?>
                        <button class="prev" onclick="prevSlide()">❮</button>
                        <button class="next" onclick="nextSlide()">❯</button>
                    <?php else: ?>
                        <img src="assets/img/no-image.png" alt="Không có ảnh">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mô tả -->
        <div class="description-section">
            <h2>Mô tả sản phẩm</h2>
            <div><?= nl2br(htmlspecialchars($product['description'])) ?></div>
        </div>

        <!-- Form đánh giá -->
        <div class="review-form">
            <h2>Đánh giá sản phẩm</h2>
            <form method="post" action="submit_review.php">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? 1 ?>">

                <label>Chọn số sao:</label>
                <select name="rating" required>
                    <option value="">--Chọn--</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> sao</option>
                    <?php endfor; ?>
                </select>

                <label>Bình luận:</label>
                <textarea name="comment" rows="3" required></textarea>

                <button type="submit">Gửi đánh giá</button>
            </form>
        </div>

        <!-- Danh sách đánh giá -->
        <div class="reviews-list">
            <h2>Đánh giá từ người mua</h2>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $r): ?>
                    <div class="review">
                        <strong><?= htmlspecialchars($r['username']) ?></strong> - <?= (int)$r['rating'] ?>/5<br>
                        <?= nl2br(htmlspecialchars($r['comment'])) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Chưa có đánh giá nào.</p>
            <?php endif; ?>
        </div>

        <!-- Sản phẩm tương tự -->
        <div class="related-products">
            <h2>Sản phẩm tương tự</h2>
            <?php if (!empty($related)): ?>
                <div class="related-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;">
                    <?php foreach ($related as $rp): ?>
                        <div class="related-item" style="border:1px solid #ddd;padding:10px;text-align:center;">
                            <a href="product_detail.php?id=<?= $rp['id'] ?>">
                                <img src="<?= htmlspecialchars($rp['image_url'] ?? 'assets/img/no-image.png') ?>"
                                    alt="<?= htmlspecialchars($rp['name']) ?>"
                                    style="width:100%;height:150px;object-fit:contain;background:#fff;">
                            </a>
                            <h3 style="font-size:16px;margin:10px 0;">
                                <a href="product_detail.php?id=<?= $rp['id'] ?>" style="text-decoration:none;color:#333;">
                                    <?= htmlspecialchars($rp['name']) ?>
                                </a>
                            </h3>
                            <p style="color:red;font-weight:bold;">
                                <?= money($rp['price']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Không có sản phẩm tương tự.</p>
            <?php endif; ?>
        </div>

    </div>


    <?php include 'footer.php'; ?>
</body>

</html>