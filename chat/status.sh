#!/bin/bash
# Comprehensive status checker for chat application

echo "=== CHAT APPLICATION STATUS ==="
echo "Date: $(date)"
echo ""

echo "=== CONTAINER STATUS ==="
docker-compose ps
echo ""

echo "=== .ONION ADDRESSES ==="
for container in chatterbox shitchat2 shitchat3; do
    CONTAINER_ID=$(docker exec $container hostname 2>/dev/null)
    if [ -n "$CONTAINER_ID" ]; then
        HOSTNAME=$(docker exec $container cat /var/www/html/hostname_${CONTAINER_ID}.txt 2>/dev/null)
        if [ -n "$HOSTNAME" ] && [ "$HOSTNAME" != "Failed to generate hostname" ]; then
            echo "✅ $container: $HOSTNAME"
        else
            echo "❌ $container: No valid hostname found"
        fi
    else
        echo "❌ $container: Container not accessible"
    fi
done
echo ""

echo "=== TOR PROCESS STATUS ==="
for container in chatterbox shitchat2 shitchat3; do
    echo "Checking $container..."
    TOR_PROCESSES=$(docker exec $container ps aux | grep tor | grep -v grep | wc -l)
    if [ "$TOR_PROCESSES" -gt 0 ]; then
        echo "  ✅ Tor is running ($TOR_PROCESSES processes)"
        docker exec $container ps aux | grep tor | grep -v grep | head -1
    else
        echo "  ❌ Tor is not running"
    fi
done
echo ""

echo "=== HOSTNAME FILES STATUS ==="
echo "Local hostname files:"
ls -la hostnames/*.onion 2>/dev/null || echo "  No local hostname files found"
echo ""
echo "Container hostname files:"
for container in chatterbox shitchat2 shitchat3; do
    CONTAINER_ID=$(docker exec $container hostname 2>/dev/null)
    if [ -n "$CONTAINER_ID" ]; then
        FILE_EXISTS=$(docker exec $container test -f /var/www/html/hostname_${CONTAINER_ID}.txt && echo "✅" || echo "❌")
        echo "  $container (${CONTAINER_ID}): hostname_${CONTAINER_ID}.txt $FILE_EXISTS"
    fi
done
echo ""

echo "=== SQLITE DATABASE STATUS ==="
for container in chatterbox shitchat2 shitchat3 chatterbox_php; do
    DB_EXISTS=$(docker exec $container test -f /var/lib/sqlite/chat*.sqlite && echo "✅" || echo "❌")
    DB_COUNT=$(docker exec $container ls -1 /var/lib/sqlite/*.sqlite 2>/dev/null | wc -l)
    echo "  $container: SQLite databases $DB_EXISTS ($DB_COUNT files)"
done
echo ""

echo "=== PERMISSION STATUS ==="
echo "Hostname directory permissions:"
ls -la hostnames/ 2>/dev/null | head -5 || echo "  No hostnames directory"
echo ""
echo "Key files in containers:"
for container in chatterbox shitchat2 shitchat3; do
    echo "  $container:"
    docker exec $container ls -la /var/lib/tor/hidden_service/hostname 2>/dev/null || echo "    No hostname file"
    docker exec $container ls -la /var/lib/sqlite/ 2>/dev/null | head -2 || echo "    No SQLite directory"
done

echo ""
echo "=== QUICK ACCESS ==="
echo "Available scripts:"
echo "  bash extract-hostnames.sh    - Extract all .onion addresses"
echo "  bash fix-permissions.sh      - Fix all file permissions"
echo "  bash check-tor.sh           - Check Tor status (legacy)"
echo "  bash status.sh              - This status report"
