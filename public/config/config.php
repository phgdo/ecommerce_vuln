<?php
$servername = "db"; // tên service MySQL trong docker-compose.yml
$dbname = "ecom";
$username = "user";
$password = "pass";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
