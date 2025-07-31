#!/bin/bash
set -e

echo "Starting Nginx + Tor services..."

# Ensure Tor directories have correct permissions
echo "Setting up Tor directory permissions..."
chown -R debian-tor:debian-tor /var/lib/tor
chmod 700 /var/lib/tor
chmod 700 /var/lib/tor/hidden_service
chmod 755 /var/log/tor
chown -R debian-tor:debian-tor /var/log/tor

# Ensure web directory permissions
echo "Setting up web directory permissions..."
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Ensure SQLite directory permissions if it exists
if [ -d /var/lib/sqlite ]; then
    echo "Setting up SQLite directory permissions..."
    chown -R www-data:www-data /var/lib/sqlite
    chmod 755 /var/lib/sqlite
    find /var/lib/sqlite -name "*.sqlite*" -exec chmod 664 {} \; 2>/dev/null || true
fi

# Install process monitoring tools
if ! command -v ps >/dev/null 2>&1; then
    apt-get update && apt-get install -y procps && rm -rf /var/lib/apt/lists/*
fi

echo "Creating log file and fixing permissions..."
touch /var/log/tor/tor.log
chown debian-tor:debian-tor /var/log/tor/tor.log
chmod 640 /var/log/tor/tor.log

echo "Testing Tor configuration..."
# Run Tor configuration test and capture output
TOR_TEST_OUTPUT=$(su debian-tor -c "tor --verify-config -f /etc/tor/torrc" 2>&1) || {
    echo "Tor configuration error: $TOR_TEST_OUTPUT"
    echo "Attempting to start anyway with basic config..."
}

echo "Starting Tor hidden service..."
# Start Tor and capture any immediate errors
su debian-tor -c "tor -f /etc/tor/torrc" &
TOR_PID=$!
echo "Tor started with PID: $TOR_PID"

# Give it a moment to initialize
sleep 3

# Check if the process is still running
if ! kill -0 $TOR_PID 2>/dev/null; then
    echo "ERROR: Tor process died immediately! Checking logs..."
    cat /var/log/tor/tor.log 2>/dev/null || echo "No log file found"
    echo "Trying to run Tor in foreground to see error:"
    su debian-tor -c "tor -f /etc/tor/torrc" || true
fi

# Wait for Tor to initialize and generate hostname
echo "Waiting up to 3 minutes for Tor to generate hidden service hostname..."
WAIT_COUNT=0
while [ ! -f /var/lib/tor/hidden_service/hostname ] && [ $WAIT_COUNT -lt 90 ]; do
    sleep 2
    WAIT_COUNT=$((WAIT_COUNT + 1))
    if [ $((WAIT_COUNT % 15)) -eq 0 ]; then
        echo "Still waiting for Tor... ($WAIT_COUNT/90)"
        # Check if Tor process is still running
        if ! kill -0 $TOR_PID 2>/dev/null; then
            echo "ERROR: Tor process died! Attempting restart with debug..."
            cat /var/log/tor/tor.log 2>/dev/null || echo "No log file found"
            su debian-tor -c "tor -f /etc/tor/torrc" &
            TOR_PID=$!
        fi
    fi
done

# Extract and save hostname
if [ -f /var/lib/tor/hidden_service/hostname ]; then
    HOSTNAME=$(cat /var/lib/tor/hidden_service/hostname)
    echo "✅ Hidden service hostname: $HOSTNAME"
    # Save to unique file based on container hostname
    CONTAINER_NAME=$(hostname)
    echo "$HOSTNAME" > /var/www/html/hostname_${CONTAINER_NAME}.txt
    chown www-data:www-data /var/www/html/hostname_${CONTAINER_NAME}.txt
    chmod 644 /var/www/html/hostname_${CONTAINER_NAME}.txt
    echo "Hostname saved to /var/www/html/hostname_${CONTAINER_NAME}.txt"
else
    echo "❌ Warning: Could not find Tor hostname file after 3 minutes"
    CONTAINER_NAME=$(hostname)
    echo "Failed to generate hostname" > /var/www/html/hostname_${CONTAINER_NAME}.txt
    echo "Final log check:"
    cat /var/log/tor/tor.log 2>/dev/null || echo "No log file found"
fi

# Function to handle shutdown
shutdown() {
    echo "Shutting down services..."
    kill $TOR_PID 2>/dev/null || true
    nginx -s quit 2>/dev/null || true
    exit 0
}

# Trap shutdown signals
trap shutdown SIGTERM SIGINT

echo "Starting Nginx..."
# Start Nginx in foreground
exec nginx -g 'daemon off;'
