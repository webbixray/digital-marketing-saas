# Staging Deployment

## Quick Start (Hybrid: Databases in Docker, App on Host)

```bash
# 1. Start databases
docker-compose -f docker-compose.hybrid.yml up -d

# 2. First-time setup: create MySQL user
docker exec dmsaas-db mysql -u root -prootpassword -e "CREATE USER IF NOT EXISTS 'dmsaas'@'%' IDENTIFIED BY 'dmspassword'; GRANT ALL PRIVILEGES ON digitalmarketingsaas.* TO 'dmsaas'@'%'; CREATE DATABASE IF NOT EXISTS digitalmarketingsaas; FLUSH PRIVILEGES;"

# 3. Run migrations
php artisan migrate --force

# 4. Start app
php artisan serve --host=0.0.0.0 --port=8000
```

## Access

- **App**: http://localhost:8000
- **DB**: localhost:3306 (dmsaas / dmspassword / digitalmarketingsaas)
- **Redis**: localhost:6379

## Services

| Service | Port | Container | Status |
|---------|------|-----------|--------|
| App (host PHP) | 8000 | — | ✅ Running |
| MySQL 8.0 | 3306 | dmsaas-db | ✅ Running |
| Redis 7 | 6379 | dmsaas-redis | ✅ Running |

## Database Credentials

| User | Password | Database |
|------|----------|----------|
| root | rootpassword | — |
| dmsaas | dmspassword | digitalmarketingsaas |

## Configuration

Current `.env` for staging:

```
APP_ENV=staging
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digitalmarketingsaas
DB_USERNAME=dmsaas
DB_PASSWORD=dmspassword
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
```

## Useful Commands

```bash
# Start databases
docker-compose -f docker-compose.hybrid.yml up -d

# Stop all
docker-compose -f docker-compose.hybrid.yml down

# View logs
docker-compose -f docker-compose.hybrid.yml logs -f

# MySQL shell
docker exec -it dmsaas-db mysql -u dmsaas -p dmspassword digitalmarketingsaas

# Redis CLI
docker exec -it dmsaas-redis redis-cli

# Run artisan
php artisan [command]
```

## Full Docker Deployment (Future)

When Docker networking is fixed:

```bash
# Build and run everything in Docker
docker-compose -f docker-compose.zero-build.yml up -d

# Access at http://localhost:8080
```

## File Reference

| File | Purpose |
|------|---------|
| `docker-compose.hybrid.yml` | Databases only (Docker) + app on host |
| `docker-compose.zero-build.yml` | Full stack (official images, no build) |
| `docker-compose.staging.yml` | Full stack with custom Dockerfile |
| `docker/nginx/default.conf` | Nginx config (fastcgi_pass app:9000) |
| `docker/mysql/my.cnf` | MySQL config (UTF8MB4, InnoDB) |
| `docker/redis/redis.conf` | Redis config (persistence, LRU) |
| `docker/php/local.ini` | PHP config (256M, UTC) |
| `docker.env` | Environment for Docker containers |
| `Dockerfile` | Custom PHP image (Alpine + Redis ext) |
| `docker-entrypoint.sh` | Container startup script |

## Troubleshooting

| Issue | Solution |
|-------|----------|
| MySQL access denied | Run the CREATE USER command above |
| Port 3306 in use | Change port in docker-compose.hybrid.yml |
| Port 8000 in use | Stop other servers or use --port=8080 |
| Redis class not found | Host lacks ext-redis; using predis instead |
| Class "Redis" not found | Switch REDIS_CLIENT=predis or install ext-redis |
