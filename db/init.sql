-- Xóa bảng nếu tồn tại (để init lại)
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS payment_items;
DROP TABLE IF EXISTS payment;
DROP TABLE IF EXISTS cart_product;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS user_bank;
DROP TABLE IF EXISTS userAddrs;
DROP TABLE IF EXISTS users;

-- ===============================
-- Bảng Users
-- ===============================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    phonenumber VARCHAR(20),
    avatar VARCHAR(255),
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lastlogin TIMESTAMP NULL,
    login_ip VARCHAR(45)
);

-- ===============================
-- Bảng User Addresses
-- ===============================
CREATE TABLE userAddrs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userid INT NOT NULL,
    address TEXT NOT NULL,
    note TEXT,
    FOREIGN KEY (userid) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================
-- Bảng User Bank Accounts
-- ===============================
CREATE TABLE user_bank (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    account_name VARCHAR(100),
    branch_name VARCHAR(100),
    swift_code VARCHAR(20),
    currency VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================
-- Bảng Cart
-- ===============================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================
-- Bảng Cart Product
-- ===============================
CREATE TABLE cart_product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cartid INT NOT NULL,
    productid INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    discount_price DECIMAL(10, 2) DEFAULT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cartid) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (productid) REFERENCES products(id)
);

-- ===============================
-- Bảng Payment
-- ===============================
CREATE TABLE payment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    total_discount_price DECIMAL(10, 2) DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ===============================
-- Bảng Payment Items
-- ===============================
CREATE TABLE payment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_product_id INT NOT NULL,
    paymentid INT NOT NULL,
    FOREIGN KEY (cart_product_id) REFERENCES cart_product(id),
    FOREIGN KEY (paymentid) REFERENCES payment(id)
);

-- ===============================
-- Bảng Comments
-- ===============================
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userid INT NOT NULL,
    productid INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userid) REFERENCES users(id),
    FOREIGN KEY (productid) REFERENCES products(id)
);

-- ===============================
-- Bảng Products
-- ===============================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    discount_price DECIMAL(10, 2) DEFAULT NULL,
    categoryid INT,
    configuration TEXT,
    description TEXT,
    remainingquantity INT DEFAULT 0,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===============================
-- Bảng Product Images
-- ===============================
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ===============================
-- Bảng Product Reviews
-- ===============================
CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);



-- Insert dữ liệu
INSERT INTO users 
(username, password, role, phonenumber, avatar, status, created_at) 
VALUES
('admin', '12345678', 1, '0905123456',  'assets/avatar/default.jpg', 1, NOW()),
('nguyenvana', '12345678', 0, '0905123456', 'assets/avatar/default.jpg', 1, NOW()),
('tranthib', 'abcdef12', 0, '0906789123', 'assets/avatar/default.jpg', 1, NOW()),
('lehoangc', 'qwerty90', 0, '0912345678', 'assets/avatar/default.jpg', 0, NOW());

-- Thêm dữ liệu vào bảng products
INSERT INTO products (name, price, discount_price, categoryid, configuration, description, remainingquantity, status, created_at, updated_at)
VALUES
('Google Pixel 9 Pro', 999.99, 899.99, 1, 'Snapdragon 8 Gen 3, 12GB RAM, 256GB Storage', 'Flagship phone from Google with best AI features.', 50, TRUE, NOW(), NOW()),
('iPhone 16 Pro Max', 1299.99, 1199.99, 1, 'Apple A18 Pro, 8GB RAM, 512GB Storage', 'Latest iPhone model with titanium body.', 30, TRUE, NOW(), NOW()),
('Samsung Galaxy S25', 1099.99, 999.99, 1, 'Snapdragon 8 Gen 3, 12GB RAM, 256GB Storage', 'High-end Android from Samsung.', 40, TRUE, NOW(), NOW()),
('Samsung Galaxy S25 Ultra', 1399.99, 1299.99, 1, 'Snapdragon 8 Gen 3, 16GB RAM, 512GB Storage', 'Ultra model with periscope zoom.', 25, TRUE, NOW(), NOW()),
('iPad Pro M4', 1199.99, 1099.99, 2, 'Apple M4 Chip, 12.9 inch Display, 256GB Storage', 'Best tablet for productivity and creativity.', 20, TRUE, NOW(), NOW()),
('MacBook M4', 1999.99, 1899.99, 3, 'Apple M4 Pro Chip, 16GB RAM, 1TB SSD', 'Powerful laptop for professionals.', 15, TRUE, NOW(), NOW()),
('Razer Blade', 2499.99, 2299.99, 4, 'Intel i9 14th Gen, RTX 4080, 32GB RAM', 'Premium gaming laptop from Razer.', 10, TRUE, NOW(), NOW());

-- Thêm ảnh vào bảng product_images
INSERT INTO product_images (product_id, image_url, created_at, updated_at) VALUES
(1, 'assets/products/google_pixel_9_pro_1.png', NOW(), NOW()),
(1, 'assets/products/google_pixel_9_pro_2.webp', NOW(), NOW()),
(1, 'assets/products/google_pixel_9_pro_3.jpg', NOW(), NOW()),
(1, 'assets/products/google_pixel_9_pro_4.jfif', NOW(), NOW()),
(1, 'assets/products/google_pixel_9_pro_5.jpg', NOW(), NOW()),
(1, 'assets/products/google_pixel_9_pro_6.jpg', NOW(), NOW()),

(2, 'assets/products/iphone16_promax_1.jpg', NOW(), NOW()),
(2, 'assets/products/iphone16_promax_2.jpg', NOW(), NOW()),
(2, 'assets/products/iphone16_promax_3.jpg', NOW(), NOW()),
(2, 'assets/products/iphone16_promax_4.jpg', NOW(), NOW()),

(3, 'assets/products/samsung-galaxy-s25-1.jpg', NOW(), NOW()),
(3, 'assets/products/samsung-galaxy-s25-2.jpg', NOW(), NOW()),

(4, 'assets/products/samsung-galaxy-s25-ultra-1.jpg', NOW(), NOW()),
(4, 'assets/products/samsung-galaxy-s25-ultra-2.jpg', NOW(), NOW()),
(4, 'assets/products/samsung-galaxy-s25-ultra-3.jpg', NOW(), NOW()),
(4, 'assets/products/samsung-galaxy-s25-ultra-4.jpg', NOW(), NOW()),

(5, 'assets/products/ipad_pro_m4_1.jpg', NOW(), NOW()),
(5, 'assets/products/ipad_pro_m4_2.jpg', NOW(), NOW()),
(5, 'assets/products/ipad_pro_m4_3.jpg', NOW(), NOW()),
(5, 'assets/products/ipad_pro_m4_4.jpg', NOW(), NOW()),

(6, 'assets/products/macbook_m4_1.jfif', NOW(), NOW()),
(6, 'assets/products/macbook_m4_2.jfif', NOW(), NOW()),
(6, 'assets/products/macbook_m4_3.jfif', NOW(), NOW()),
(6, 'assets/products/macbook_m4_4.jpg', NOW(), NOW()),
(6, 'assets/products/macbook_m4_5.jfif', NOW(), NOW()),
(6, 'assets/products/macbook_m4_6.jfif', NOW(), NOW()),

(7, 'assets/products/razer-blade-1.jpg', NOW(), NOW()),
(7, 'assets/products/razer-blade-2.jfif', NOW(), NOW()),
(7, 'assets/products/razer-blade-3.jfif', NOW(), NOW()),
(7, 'assets/products/razer-blade-4.jpg', NOW(), NOW()),
(7, 'assets/products/razer-blade-5.jpg', NOW(), NOW());