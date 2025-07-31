#!/bin/bash
# Permission fixer script - can be run manually or via cron

echo "=== Fixing File and Folder Permissions ==="

# Fix web directory permissions
echo "Fixing /var/www/html permissions..."
if [ -d /var/www/html ]; then
    chown -R www-data:www-data /var/www/html
    find /var/www/html -type d -exec chmod 755 {} \;
    find /var/www/html -type f -exec chmod 644 {} \;
    echo "✅ Web directory permissions fixed"
fi

# Fix SQLite permissions in all containers
for container in chatterbox shitchat2 shitchat3 chatterbox_php; do
    if docker ps --format "table {{.Names}}" | grep -q "^${container}$"; then
        echo "Fixing SQLite permissions in $container..."
        docker exec "$container" bash -c '
            if [ -d /var/lib/sqlite ]; then
                chown -R www-data:www-data /var/lib/sqlite
                chmod 755 /var/lib/sqlite
                find /var/lib/sqlite -name "*.sqlite*" -exec chmod 664 {} \; 2>/dev/null || true
                echo "✅ SQLite permissions fixed in '$container'"
            fi
        ' 2>/dev/null || echo "⚠️  Could not fix SQLite permissions in $container"
    fi
done

# Fix Tor permissions in nginx containers
for container in chatterbox shitchat2 shitchat3; do
    if docker ps --format "table {{.Names}}" | grep -q "^${container}$"; then
        echo "Fixing Tor permissions in $container..."
        docker exec "$container" bash -c '
            if [ -d /var/lib/tor ]; then
                chown -R debian-tor:debian-tor /var/lib/tor
                chmod 700 /var/lib/tor
                chmod 700 /var/lib/tor/hidden_service 2>/dev/null || true
                echo "✅ Tor permissions fixed in '$container'"
            fi
        ' 2>/dev/null || echo "⚠️  Could not fix Tor permissions in $container"
    fi
done

# Fix hostname files permissions
echo "Fixing hostname file permissions..."
if [ -d /var/www/html ]; then
    find . -name "hostname_*.txt" -exec chmod 644 {} \; 2>/dev/null || true
    find . -name "hostname_*.txt" -exec chown www-data:www-data {} \; 2>/dev/null || true
    echo "✅ Hostname file permissions fixed"
fi

echo "=== Permission fixing complete ==="
