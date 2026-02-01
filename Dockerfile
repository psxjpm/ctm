# 使用官方PHP-Apache镜像作为基础
FROM php:8.1-apache

# 安装 systemd 和 pdo_mysql 扩展所需的依赖及扩展本身
# 注意：容器内使用 apt-get 包管理器
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli mbstring zip exif pcntl bcmath

# 启用Apache的rewrite模块（为未来功能预留）
RUN a2enmod rewrite

# 将Apache文档根目录设置为我们的项目子目录
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 复制项目文件（由docker-compose挂载完成，此步骤可选）
# COPY html/ /var/www/html/