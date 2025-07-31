#!/bin/bash
set -e

echo "Setting up PHP-FPM permissions..."

# Ensure web directory permissions
echo "Fixing web directory permissions..."
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Ensure SQLite directory permissions
if [ -d /var/lib/sqlite ]; then
    echo "Fixing SQLite directory permissions..."
    chown -R www-data:www-data /var/lib/sqlite
    chmod 755 /var/lib/sqlite
    find /var/lib/sqlite -name "*.sqlite*" -exec chmod 664 {} \; 2>/dev/null || true
fi

# Make PHP files executable if needed
find /var/www/html -name "*.php" -exec chmod 644 {} \;

# Ensure log directories are writable
if [ -d /var/log/php ]; then
    chown -R www-data:www-data /var/log/php
    chmod 755 /var/log/php
fi

echo "Starting PHP-FPM..."
exec docker-php-entrypoint php-fpm
