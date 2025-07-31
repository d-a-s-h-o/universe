# Tor Hidden Service Setup

This setup configures Tor hidden services for your chat application, with Tor running in each nginx container, making each chat instance accessible via its own .onion address.

## What's Included

### Files Added:
- `torrc` - Tor configuration file
- `Dockerfile.nginx` - Custom Nginx+Tor Docker image
- `nginx-tor-start.sh` - Startup script for Nginx containers that runs both Tor and Nginx
- `check-tor.sh` - Script to check Tor service status and hostnames
- Updated `docker-compose.yml` with separate Tor data volumes for each nginx container

### Architecture:
- **PHP-FPM Container**: Runs PHP application only (no Tor)
- **Nginx Containers**: Each runs Nginx + Tor with its own hidden service
- **Separate Tor Instances**: Each nginx container has its own Tor process and .onion address
- **Independent Databases**: Each chat instance uses its own SQLite database

### Features:
- **Hidden Service on Port 80**: Each nginx container exposes port 80 as a hidden service
- **Persistent Hostnames**: Each .onion address is saved to `hostnames.txt` in each container
- **V3 Onion Addresses**: Uses latest Tor hidden service protocol
- **Automatic Startup**: Tor starts automatically with each nginx container
- **Isolated Services**: Each chat has its own Tor circuit and .onion address

## Usage

### 1. Build and Start Services:
```bash
docker-compose build
docker-compose up -d
```

### 2. Check Tor Status and Hostnames:
```bash
./check-tor.sh
```

### 3. View Generated Hostnames:
```bash
# Check all hostnames
./check-tor.sh

# Or check individual container hostnames
docker exec chatterbox cat /var/www/html/hostnames.txt
docker exec shitchat2 cat /var/www/html/hostnames.txt  
docker exec shitchat3 cat /var/www/html/hostnames.txt

# Hostnames are also saved to local files
cat chatterbox_hostname.txt
cat shitchat2_hostname.txt
cat shitchat3_hostname.txt
```

### 4. Access Your Chats via Tor:
Each chat instance has its own .onion address:
- **chatterbox**: Access via its unique .onion address
- **shitchat2**: Access via its unique .onion address  
- **shitchat3**: Access via its unique .onion address

Use a Tor browser to access each chat using their respective .onion addresses.

## Configuration

### Tor Configuration (`torrc`):
- **HiddenServiceDir**: `/var/lib/tor/hidden_service/`
- **HiddenServicePort**: Maps port 80 to localhost:80
- **HiddenServiceVersion**: 3 (latest protocol)

### Docker Volumes:
- `tor_data_1`, `tor_data_2`, `tor_data_3`: Persistent storage for each nginx container's Tor keys and hostnames
- `sqlite_data_1`, `sqlite_data_2`, `sqlite_data_3`: Separate SQLite databases for each chat
- Each nginx container maintains its own isolated Tor hidden service

### Container Architecture:
- **php-fpm**: Processes PHP requests (no Tor)  
- **nginx-1 (chatterbox)**: Nginx + Tor with unique .onion address
- **nginx-2 (shitchat2)**: Nginx + Tor with unique .onion address
- **nginx-3 (shitchat3)**: Nginx + Tor with unique .onion address

### Environment Variables:
No additional environment variables needed for Tor - it uses default configuration.

## Security Notes

1. **Persistent Keys**: Tor private keys are stored in Docker volumes for consistent .onion addresses
2. **V3 Addresses**: Using the latest Tor hidden service protocol for better security
3. **Isolated Services**: Each container can have its own .onion address
4. **Automatic Key Generation**: Tor generates cryptographic keys automatically

## Troubleshooting

### Check if Tor is Running:
```bash
# Check Tor in specific containers
docker exec chatterbox ps aux | grep tor
docker exec shitchat2 ps aux | grep tor  
docker exec shitchat3 ps aux | grep tor
```

### View Tor Logs:
```bash
docker-compose logs chatterbox | grep -i tor
docker-compose logs shitchat2 | grep -i tor
docker-compose logs shitchat3 | grep -i tor
```

### Manually Check Hostname:
```bash
docker exec chatterbox cat /var/lib/tor/hidden_service/hostname
docker exec shitchat2 cat /var/lib/tor/hidden_service/hostname
docker exec shitchat3 cat /var/lib/tor/hidden_service/hostname
```

### Restart Services:
```bash
docker-compose restart
```

## Port Information

- **9050**: Tor SOCKS proxy (exposed)
- **9051**: Tor control port (exposed)
- **80**: Hidden service port (internal)

The .onion addresses will be automatically generated and saved to `hostnames.txt` when the containers start.
