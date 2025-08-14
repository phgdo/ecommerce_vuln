<?php
session_start();
require 'config/config.php'; // $conn mysqli connection

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_min = isset($_GET['price_min']) ? trim($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? trim($_GET['price_max']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'name';
$order = isset($_GET['order']) ? trim($_GET['order']) : 'asc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$limit = 24;
$offset = ($page - 1) * $limit;

$where_clauses = [];
$params = [];
$types = '';

if ($q !== '') {
    $where_clauses[] = "(p.name LIKE CONCAT('%', ?, '%') OR p.description LIKE CONCAT('%', ?, '%'))";
    $params[] = $q;
    $params[] = $q;
    $types .= 'ss';
}
if ($category !== '') {
    $where_clauses[] = "p.categoryid = ?";
    $params[] = $category;
    $types .= 'i';
}
if ($price_min !== '') {
    $where_clauses[] = "p.price >= ?";
    $params[] = $price_min;
    $types .= 'd';
}
if ($price_max !== '') {
    $where_clauses[] = "p.price <= ?";
    $params[] = $price_max;
    $types .= 'd';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$allowed_sort = ['name', 'price', 'rating', 'purchases'];
$allowed_order = ['asc', 'desc'];
if (!in_array(strtolower($sort), $allowed_sort)) $sort = 'name';
if (!in_array(strtolower($order), $allowed_order)) $order = 'asc';

switch ($sort) {
    case 'rating':
        $sort_sql = 'avg_rating';
        break;
    case 'purchases':
        $sort_sql = 'total_purchases';
        break;
    default:
        $sort_sql = "p.$sort";
}

// Lấy danh sách sản phẩm + ảnh đầu tiên
$sql = "
SELECT 
    p.id, p.name, p.price, p.discount_price, p.remainingquantity, p.description,
    IFNULL(AVG(pr.rating), 0) AS avg_rating,
    (
        SELECT COUNT(*) 
        FROM payment_items pi
        JOIN cart_product cp ON pi.cart_product_id = cp.id
        WHERE cp.productid = p.id
    ) AS total_purchases
FROM products p
LEFT JOIN product_reviews pr ON pr.product_id = p.id
$where_sql
GROUP BY p.id
ORDER BY $sort_sql $order
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error . "\nQuery: " . $sql);
}

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$products = $res->fetch_all(MYSQLI_ASSOC);

// Lấy ảnh cho từng sản phẩm
foreach ($products as &$p) {
    $img_stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
    $img_stmt->bind_param("i", $p['id']);
    $img_stmt->execute();
    $img_res = $img_stmt->get_result();
    $images = [];
    while ($row = $img_res->fetch_assoc()) {
        $images[] = $row['image_url'];
    }
    if (empty($images)) {
        $images[] = 'assets/img/no-image.png';
    }
    $p['images'] = $images;
}
unset($p);

function money($v)
{
    return number_format($v, 2, '.', ',') . '$'; // Giữ 2 số thập phân, dấu . làm phân cách thập phân
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Sản phẩm</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            cursor: pointer;
        }

        .product-slider {
            height: 160px;
            overflow: hidden;
        }

        .product-slider img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.5s ease;
        }

        .qty-input {
            width: 60px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #ff7e5f, #feb47b);
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, #ff6a3d, #ffb347);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .btn-add-cart:active {
            transform: scale(0.97);
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <main>
        <section style="padding:20px; max-width:1100px; margin:0 auto;">
            <h1>Danh sách sản phẩm</h1>
            <form method="get" action="products.php" style="margin-bottom:16px;">
                <input type="text" name="q" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($q) ?>">
                <input type="text" name="category" placeholder="category id" value="<?= htmlspecialchars($category) ?>">
                <input type="text" name="price_min" placeholder="Giá từ" value="<?= htmlspecialchars($price_min) ?>">
                <input type="text" name="price_max" placeholder="Giá đến" value="<?= htmlspecialchars($price_max) ?>">
                <label>Sort:
                    <select name="sort">
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên</option>
                        <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Giá</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Đánh giá</option>
                        <option value="purchases" <?= $sort === 'purchases' ? 'selected' : '' ?>>Lượt mua</option>
                    </select>
                </label>
                <label>Order:
                    <select name="order">
                        <option value="asc" <?= $order === 'asc' ? 'selected' : '' ?>>Asc</option>
                        <option value="desc" <?= $order === 'desc' ? 'selected' : '' ?>>Desc</option>
                    </select>
                </label>
                <button type="submit">Lọc</button>
            </form>

            <div class="grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <div class="card">
                            <a href="product_detail.php?id=<?= $p['id'] ?>" style="text-decoration:none; color:inherit;">
                                <div class="product-slider" data-images='<?= json_encode($p['images']) ?>'>
                                    <img src="<?= htmlspecialchars($p['images'][0]) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                </div>
                            </a>
                            <div style="padding:12px;">
                                <a href="product_detail.php?id=<?= $p['id'] ?>" style="text-decoration:none; color:inherit;">
                                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                                </a>
                                <a href="product_detail.php?id=<?= $p['id'] ?>" style="text-decoration:none; color:inherit;">
                                    <p style="color:#666; font-size:13px; height:36px; overflow:hidden;"><?= htmlspecialchars($p['description']) ?></p>
                                </a>
                                <?php if ($p['remainingquantity'] == 0): ?>
                                    <div style="background:#e74c3c; color:#fff; padding:6px; border-radius:6px;">ĐÃ HẾT</div>
                                <?php else: ?>
                                    <div style="font-weight:bold; color:#28a745;">
                                        <?= money($p['discount_price'] ?? $p['price']) ?>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top:8px; font-size:12px; color:#999;">
                                    Rating: <?= round($p['avg_rating'], 1) ?> | Lượt mua: <?= intval($p['total_purchases']) ?>
                                </div>

                                <!-- Form thêm vào giỏ -->
                                <form method="POST" action="add_to_cart.php" style="margin:0; display:flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="number" name="qty" value="1" min="1" class="qty-input">
                                    <button type="submit" class="btn-add-cart">🛒 Thêm vào giỏ</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>Không có sản phẩm nào.</div>
                <?php endif; ?>
            </div>

            <div style="margin-top:20px; text-align:center;">
                <a href="?q=<?= urlencode($q) ?>&category=<?= urlencode($category) ?>&price_min=<?= urlencode($price_min) ?>&price_max=<?= urlencode($price_max) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&page=<?= max(1, $page - 1) ?>">« Prev</a>
                &nbsp; Page <?= $page ?> &nbsp;
                <a href="?q=<?= urlencode($q) ?>&category=<?= urlencode($category) ?>&price_min=<?= urlencode($price_min) ?>&price_max=<?= urlencode($price_max) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&page=<?= $page + 1 ?>">Next »</a>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>

    <script>
        document.querySelectorAll('.product-slider').forEach(slider => {
            let images = JSON.parse(slider.dataset.images);
            let img = slider.querySelector('img');
            let index = 0;
            let interval;

            slider.addEventListener('mouseenter', () => {
                if (images.length > 1) {
                    interval = setInterval(() => {
                        index = (index + 1) % images.length;
                        img.style.opacity = 0;
                        setTimeout(() => {
                            img.src = images[index];
                            img.style.opacity = 1;
                        }, 300);
                    }, 1500);
                }
            });

            slider.addEventListener('mouseleave', () => {
                clearInterval(interval);
                index = 0;
                img.src = images[index];
            });
        });
    </script>
</body>

</html>