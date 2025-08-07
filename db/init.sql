-- Tạo bảng người dùng với mật khẩu plaintext (vulnerable)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT 0
);

INSERT INTO users (username, password, is_admin) VALUES
('admin', 'admin123', 1),     -- plaintext password
('user1', 'password1', 0);

-- Tạo bảng sản phẩm
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10, 2)
);

INSERT INTO products (name, description, price) VALUES
('Laptop ABC', 'Sản phẩm có thể bị XSS <script>alert(1)</script>', 999.99),
('Phone XYZ', 'Không kiểm tra đầu vào', 499.99);
