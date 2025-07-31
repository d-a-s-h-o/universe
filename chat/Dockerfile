FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    sqlite3 \
    libsqlite3-dev \
    libcurl4-openssl-dev \
    libjpeg-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure GD extension
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype

# Install PHP extensions
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install pdo_sqlite
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install curl
RUN docker-php-ext-install zip
RUN docker-php-ext-install gd

# Set working directory
WORKDIR /var/www/html

# Create directories and set initial permissions
RUN mkdir -p /var/www/html/data /var/lib/sqlite && \
    chown -R www-data:www-data /var/www/html /var/lib/sqlite

# Copy startup script
COPY php-fpm-start.sh /usr/local/bin/php-fpm-start.sh
RUN chmod +x /usr/local/bin/php-fpm-start.sh

# Set environment variable for SQLite
ENV SQLITE_DB_PATH=/var/lib/sqlite/chat.sqlite

# Use custom startup script
CMD ["/usr/local/bin/php-fpm-start.sh"]
