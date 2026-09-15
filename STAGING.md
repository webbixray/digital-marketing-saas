# Staging Environment Setup

## Docker Desktop Installation (Windows)

Since Docker Desktop requires a GUI installer, please follow these steps:

### Step 1: Download Docker Desktop
1. Go to: https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe
2. Download the installer

### Step 2: Install Docker Desktop
1. Run the installer
2. Make sure "Use WSL 2 instead of Hyper-V" is selected
3. Click Install
4. Restart your computer when prompted

### Step 3: Start Docker Desktop
1. Open Docker Desktop from Start Menu
2. Wait for it to start (whale icon in system tray)
3. Accept the license agreement if prompted

### Step 4: Verify Installation
```bash
docker --version
docker-compose version
```

## Staging Environment Files

The following files have been prepared for your staging environment:

| File | Purpose |
|------|---------|
| `docker-compose.staging.yml` | Docker Compose configuration for staging |
| `Dockerfile.staging` | Docker image with PHP 8.4, Nginx, Node.js |
| `docker/nginx/default.conf` | Nginx server configuration |
| `docker/mysql/my.cnf` | MySQL configuration for staging |
| `docker/redis/redis.conf` | Redis configuration for staging |

## Quick Start (After Docker Install)

```bash
# Navigate to project
cd C:\xampp\htdocs\digitalmarketingsaas

# Start staging environment
docker-compose -f docker-compose.staging.yml up -d

# Run database migrations
docker-compose -f docker-compose.staging.yml exec app php artisan migrate --force

# Seed demo data (optional)
docker-compose -f docker-compose.staging.yml exec app php artisan db:seed --force

# Access the application
# http://localhost:8080
```

## Staging Configuration

The staging environment uses:

- **App**: PHP 8.4 + Nginx + Node.js
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis 7
- **Port**: 8080 (change in docker-compose.staging.yml if needed)

## Useful Commands

```bash
# View logs
docker-compose -f docker-compose.staging.yml logs -f

# Stop all services
docker-compose -f docker-compose.staging.yml down

# Rebuild after code changes
docker-compose -f docker-compose.staging.yml up -d --build

# Run artisan commands
docker-compose -f docker-compose.staging.yml exec app php artisan [command]

# Access MySQL
docker-compose -f docker-compose.staging.yml exec db mysql -u dmsaas -p

# Access Redis
docker-compose -f docker-compose.staging.yml exec redis redis-cli

# Clear caches
docker-compose -f docker-compose.staging.yml exec app php artisan cache:clear
docker-compose -f docker-compose.staging.yml exec app php artisan config:clear
docker-compose -f docker-compose.staging.yml exec app php artisan route:clear
docker-compose -f docker-compose.staging.yml exec app php artisan view:clear
```

## Troubleshooting

### Docker won't start
- Enable Virtualization in BIOS (Intel VT-x / AMD-V)
- Enable Hyper-V or WSL2 in Windows Features
- Restart computer

### Port already in use
- Change ports in `docker-compose.staging.yml`
- Default: 8080 (app), 3306 (mysql), 6379 (redis)

### Database connection failed
- Wait 30 seconds for MySQL to start
- Check credentials in docker-compose.staging.yml

## Production Deployment

For production deployment, use the main `docker-compose.yml` file with production environment variables.
