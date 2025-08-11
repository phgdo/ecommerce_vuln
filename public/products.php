<?php
// products.php (VULNERABLE LAB) - ONLY for local training
// WARNING: This file intentionally contains many security vulnerabilities.
// DO NOT deploy to production or any public network.

session_start();
require 'config/config.php'; // $conn mysqli connection

// -----------------------------
// Read incoming params (all are UNSAFE - used intentionally)
// -----------------------------
$q = isset($_GET['q']) ? $_GET['q'] : '';                 // search term (reflected XSS)
$category = isset($_GET['category']) ? $_GET['category'] : ''; // category id (no validation)
$price_min = isset($_GET['price_min']) ? $_GET['price_min'] : ''; // price filters (no validation)
$price_max = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';    // sort field: name,price,rating,purchases
$order = isset($_GET['order']) ? $_GET['order'] : 'asc'; // asc/desc (no validation)
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 24;
$offset = ($page - 1) * $limit;

// note: the following SQL construction is intentionally vulnerable to SQL Injection
$where_clauses = [];
if ($q !== '') {
    // vulnerable: no escaping, used directly
    $where_clauses[] = "(name LIKE '%$q%' OR description LIKE '%$q%')";
}
if ($category !== '') {
    // vulnerable: no validation, could be SQL injected
    $where_clauses[] = "category_id = $category";
}
if ($price_min !== '') {
    // vulnerable: no numeric check
    $where_clauses[] = "price >= $price_min";
}
if ($price_max !== '') {
    // vulnerable: no numeric check
    $where_clauses[] = "price <= $price_max";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// vulnerable sort injection: sort and order are used directly
$order_by = "ORDER BY $sort $order";

// pagination (no protection against very large offsets, DoS)
$sql = "SELECT id, name, description, price, image_path, stock, rating, purchases
        FROM products
        $where_sql
        $order_by
        LIMIT $limit OFFSET $offset";

// execute query (and intentionally reveal errors)
$res = $conn->query($sql);
if ($res === false) {
    // VULNERABLE: reveal SQL error + query (information disclosure)
    $error_msg = "DB error: " . $conn->error . " -- Query: " . $sql;
} else {
    $products = $res->fetch_all(MYSQLI_ASSOC);
}

// helper to format price
function money($v)
{
    return number_format($v, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Sản phẩm - NNshop (Vulnerable)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header>
        <a href="index.php" class="logo">NNshop</a>
        <nav>
            <a href="index.php">Home</a>
            <a href="#">Shop</a>
        </nav>
    </header>

    <main>
        <section style="padding:20px; max-width:1100px; margin:0 auto;">
            <h1>Danh sách sản phẩm</h1>

            <!-- Search form (reflected XSS if q contains script) -->
            <form method="get" action="products.php" style="margin-bottom:16px;">
                <input type="text" name="q" placeholder="Tìm kiếm..." value="<?= $q ?>"> <!-- reflected XSS -->
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

            <!-- Show raw error if any (VULNERABLE) -->
            <?php if (!empty($error_msg)): ?>
                <div style="color:red; background:#fee; padding:10px; border-radius:6px;">
                    <?= nl2br(htmlspecialchars($error_msg)) ?>
                </div>
            <?php endif; ?>

            <!-- Results -->
            <div class="grid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px;">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <div class="card" style="background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.08); overflow:hidden;">
                            <!-- UNSAFE: image_path from DB used directly (path traversal risk if DB manipulated) -->
                            <div style="height:160px; overflow:hidden;">
                                <img src="<?= $p['image_path'] ?>" alt="<?= $p['name'] ?>" style="width:100%; height:100%; object-fit:cover;">
                            </div>

                            <div style="padding:12px;">
                                <!-- STORED XSS: name/description printed without escaping -->
                                <h3 style="margin:0 0 8px; font-size:16px;"><?= $p['name'] ?></h3>
                                <p style="margin:0 0 8px; color:#666; font-size:13px; height:36px; overflow:hidden;"><?= $p['description'] ?></p>

                                <?php if ($p['stock'] == 0): ?>
                                    <div style="display:inline-block; padding:6px 8px; background:#e74c3c; color:#fff; border-radius:6px; font-weight:bold;">ĐÃ HẾT</div>
                                <?php else: ?>
                                    <div style="font-weight:bold; color:#28a745;"><?= money($p['price']) ?></div>
                                <?php endif; ?>

                                <div style="margin-top:8px; display:flex; justify-content:space-between; align-items:center;">
                                    <!-- Product detail link uses id directly (IDOR-like if detail shows sensitive data) -->
                                    <a href="product_detail.php?id=<?= $p['id'] ?>">Xem chi tiết</a>

                                    <!-- Add-to-cart form (no CSRF protection, vulnerable to CSRF) -->
                                    <form method="POST" action="add_to_cart.php" style="margin:0;">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="number" name="qty" value="1" min="1" style="width:60px;">
                                        <button type="submit">Thêm vào giỏ</button>
                                        <!-- open redirect: if attacker sets ?return=... then add_to_cart can redirect there (if add_to_cart implements that) -->
                                    </form>
                                </div>

                                <div style="margin-top:8px; font-size:12px; color:#999;">
                                    Rating: <?= intval($p['rating']) ?> |
                                    Purchases: <?= intval($p['purchases']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>Không có sản phẩm nào.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination (very simple, no protection) -->
            <div style="margin-top:20px; text-align:center;">
                <a href="products.php?q=<?= urlencode($q) ?>&category=<?= htmlspecialchars($category) ?>&price_min=<?= htmlspecialchars($price_min) ?>&price_max=<?= htmlspecialchars($price_max) ?>&sort=<?= htmlspecialchars($sort) ?>&order=<?= htmlspecialchars($order) ?>&page=<?= max(1, $page - 1) ?>">« Prev</a>
                &nbsp; Page <?= $page ?> &nbsp;
                <a href="products.php?q=<?= urlencode($q) ?>&category=<?= htmlspecialchars($category) ?>&price_min=<?= htmlspecialchars($price_min) ?>&price_max=<?= htmlspecialchars($price_max) ?>&sort=<?= htmlspecialchars($sort) ?>&order=<?= htmlspecialchars($order) ?>&page=<?= ($page + 1) ?>">Next »</a>
            </div>

            <hr style="margin-top:24px;">
        </section>
    </main>

    <footer style="padding:20px; text-align:center; color:#666;">
        &copy; <?= date('Y') ?> NNshop
    </footer>
</body>

</html>