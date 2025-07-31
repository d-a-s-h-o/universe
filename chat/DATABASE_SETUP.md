# Chat Application with Custom SQLite Databases

This Docker setup allows each chat instance to use its own SQLite database, configured via environment variables.

## Setup Instructions

1. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit the `.env` file** to customize your database paths:
   ```env
   # User and Group IDs for file permissions
   UID=1000
   GID=1000

   # SQLite Database Paths for each chat instance
   SQLITE_DB_PATH=/var/lib/sqlite/chat.sqlite
   SQLITE_DB_PATH_1=/var/lib/sqlite/general_chat.sqlite
   SQLITE_DB_PATH_2=/var/lib/sqlite/support_chat.sqlite
   SQLITE_DB_PATH_3=/var/lib/sqlite/private_chat.sqlite
   ```

3. **Build and start the containers:**
   ```bash
   docker-compose build
   docker-compose up -d
   ```

## Database Configuration

Each service uses its own SQLite database:

- **php-fpm**: Uses `SQLITE_DB_PATH` (default: `/var/lib/sqlite/chat.sqlite`)
- **nginx-1 (chatterbox)**: Uses `SQLITE_DB_PATH_1` (default: `/var/lib/sqlite/chat1.sqlite`)
- **nginx-2 (shitchat2)**: Uses `SQLITE_DB_PATH_2` (default: `/var/lib/sqlite/chat2.sqlite`)
- **nginx-3 (shitchat3)**: Uses `SQLITE_DB_PATH_3` (default: `/var/lib/sqlite/chat3.sqlite`)

## Persistent Storage

Each chat instance has its own Docker volume for persistent SQLite database storage:

- `sqlite_data`: For php-fpm service
- `sqlite_data_1`: For nginx-1 service
- `sqlite_data_2`: For nginx-2 service
- `sqlite_data_3`: For nginx-3 service

## Database Access

To access a specific chat's database for maintenance:

```bash
# Access the database for chat1
docker-compose exec nginx-1 sqlite3 /var/lib/sqlite/chat1.sqlite

# Access the database for chat2
docker-compose exec nginx-2 sqlite3 /var/lib/sqlite/chat2.sqlite
```

## PHP Extensions Included

The custom Docker image includes all necessary PHP extensions:

- PDO (with SQLite, MySQL, PostgreSQL support)
- JSON
- MBSTRING
- CURL
- GD (for image processing/captchas)
- HASH
- SESSION
- OPENSSL
- ZIP

## Environment Variables

You can override the default database paths by setting these environment variables:

- `SQLITE_DB_PATH`: Main chat database path
- `SQLITE_DB_PATH_1`: Chat 1 database path
- `SQLITE_DB_PATH_2`: Chat 2 database path
- `SQLITE_DB_PATH_3`: Chat 3 database path

The PHP application will automatically use the environment variable if set, otherwise it falls back to the default `super_chat.sqlite`.
