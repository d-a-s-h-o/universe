#!/bin/bash
# Extract .onion hostnames from all containers

echo "=== Extracting .onion hostnames ==="

# Create output directory if it doesn't exist
mkdir -p hostnames 2>/dev/null || {
    echo "Using /tmp for hostname files due to permission restrictions"
    OUTPUT_DIR="/tmp/hostnames"
    mkdir -p "$OUTPUT_DIR"
}
OUTPUT_DIR="${OUTPUT_DIR:-hostnames}"

for container in chatterbox shitchat2 shitchat3; do
    echo "Checking $container..."
    
    # Get container hostname to find the unique file
    CONTAINER_ID=$(docker exec $container hostname 2>/dev/null)
    
    if [ -n "$CONTAINER_ID" ]; then
        # Try to get hostname from unique file
        HOSTNAME=$(docker exec $container cat /var/www/html/hostname_${CONTAINER_ID}.txt 2>/dev/null)
        
        if [ -n "$HOSTNAME" ] && [ "$HOSTNAME" != "Failed to generate hostname" ]; then
            echo "$HOSTNAME" > "${OUTPUT_DIR}/${container}.onion" 2>/dev/null || {
                echo "$HOSTNAME" > "/tmp/${container}.onion"
                echo "  ✅ ${container}: $HOSTNAME (saved to /tmp/${container}.onion)"
                continue
            }
            chmod 644 "${OUTPUT_DIR}/${container}.onion" 2>/dev/null || true
            echo "  ✅ ${container}: $HOSTNAME (saved to ${OUTPUT_DIR}/${container}.onion)"
        else
            echo "  ❌ ${container}: No valid hostname found"
        fi
    else
        echo "  ❌ ${container}: Container not accessible"
    fi
done

echo ""
echo "=== Summary ==="
if [ -d "$OUTPUT_DIR" ]; then
    ls -la "${OUTPUT_DIR}"/*.onion 2>/dev/null || echo "No hostname files created"
else
    ls -la /tmp/*.onion 2>/dev/null || echo "No hostname files created"
fi
