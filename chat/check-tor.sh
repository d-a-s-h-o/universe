#!/bin/bash

echo "=== Tor Hidden Service Status ==="

# Check if containers are running
echo "Checking container status..."
docker-compose ps

echo ""
echo "=== Hidden Service Hostnames ==="

# Check hostname directly from nginx containers
for container in chatterbox shitchat2 shitchat3; do
    echo "Checking $container..."
    if docker exec -t $container test -f /var/www/html/hostnames.txt 2>/dev/null; then
        echo "$container hostname:"
        docker exec -t $container cat /var/www/html/hostnames.txt 2>/dev/null || echo "  Could not read hostname file"
    else
        echo "  No hostname file found"
    fi
    
    # Also check Tor service status in this container
    echo "  Tor process status:"
    docker exec -t $container ps aux | grep tor | grep -v grep || echo "    Tor process not found"
    echo ""
done

echo "=== Copy hostnames to local directory ==="
# Copy hostnames from containers to local files
for container in chatterbox shitchat2 shitchat3; do
    if docker exec -t $container test -f /var/www/html/hostnames.txt 2>/dev/null; then
        docker exec -t $container cat /var/www/html/hostnames.txt > "${container}_hostname.txt" 2>/dev/null
        echo "Saved ${container} hostname to ${container}_hostname.txt"
    fi
done
