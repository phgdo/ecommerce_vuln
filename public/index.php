<?php
$conn = new mysqli("db", "user", "pass", "ecom");

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM products");

echo "<h1>Danh sách sản phẩm</h1><ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>" . htmlspecialchars($row['name']) . " - $" . $row['price'] . "</li>";
}
echo "</ul>";
