# Sử dụng image chính thức có PHP 7.2 + Apache
FROM php:7.2-apache

# Cài đặt các extension PHP cần thiết
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Bật mod_rewrite (nếu muốn clean URL)
RUN a2enmod rewrite

# Copy mã nguồn vào thư mục web
COPY ./public /var/www/html

# Đặt quyền
RUN chown -R www-data:www-data /var/www/html
