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

// mapping sort
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

// SQL
$sql = "
SELECT 
    p.id, p.name, p.price, p.discount_price, p.remainingquantity, p.description,
    (SELECT image_url FROM product_images WHERE product_id = p.id LIMIT 1) AS image_url,
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

// Chuẩn bị statement
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

function money($v)
{
    return number_format($v, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Sản phẩm</title>
    <link rel="stylesheet" href="assets/css/style.css">
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

            <div class="grid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px;">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <div class="card" style="background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.08); overflow:hidden;">
                            <div style="height:160px; overflow:hidden;">
                                <img src="<?= htmlspecialchars($p['image_url'] ?: 'assets/img/no-image.png') ?>"
                                    alt="<?= htmlspecialchars($p['name']) ?>"
                                    style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <div style="padding:12px;">
                                <h3><?= htmlspecialchars($p['name']) ?></h3>
                                <p style="color:#666; font-size:13px; height:36px; overflow:hidden;"><?= htmlspecialchars($p['description']) ?></p>
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
                                <a href="product_detail.php?id=<?= $p['id'] ?>">Xem chi tiết</a>
                                <form method="POST" action="add_to_cart.php" style="margin:0;">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="number" name="qty" value="1" min="1" style="width:60px;">
                                    <button type="submit">Thêm vào giỏ</button>
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
</body>

</html>