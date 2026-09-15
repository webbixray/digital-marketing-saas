# Digital Marketing SaaS - Production Deployment Guide

## Prerequisites

- Docker & Docker Compose
- MySQL 8.0+
- Redis 7.0+
- PHP 8.4+
- Composer 2.x

## Quick Start

1. **Clone and configure:**
   ```bash
   git clone https://github.com/webbixray/digital-marketing-saas.git
   cd digital-marketing-saas
   cp .env.example .env
   php artisan key:generate
   ```

2. **Install dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   ```

3. **Configure environment:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=digitalmarketingsaas
   DB_USERNAME=dmsaas
   DB_PASSWORD=your_secure_password

   REDIS_HOST=redis
   REDIS_PORT=6379

   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   CACHE_DRIVER=redis

   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailgun.org
   MAIL_PORT=587
   MAIL_USERNAME=postmaster@your-domain.com
   MAIL_PASSWORD=your_mail_password
   ```

4. **Run migrations and seed:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

5. **Start services:**
   ```bash
   docker-compose up -d
   ```

## Queue Workers

Queue workers run automatically via Docker Compose. For manual setup:

```bash
# Start queue worker
php artisan queue:work redis --sleep=3 --tries=3

# Start scheduler
php artisan schedule:work
```

## Scheduled Tasks

The following tasks run automatically:

| Task | Frequency | Command |
|------|-----------|---------|
| Process scheduled posts | Every minute | `posts:process-scheduled` |
| Retry failed posts | Every 5 minutes | `posts:retry-failed` |
| Clean up old logs | Weekly | `model:prune` |
| Self-improvement analysis | Daily | `agents:improve --auto-tune` |
| Security audit | Every 6 hours | `agents:audit --auto-fix` |
| System backup | Daily at 2:00 AM | `system:backup --compress` |
| System cleanup | Daily at 3:00 AM | `system:cleanup --all` |

## API Authentication

The API uses Laravel Sanctum for token-based authentication.

1. **Create a token:**
   ```bash
   curl -X POST https://your-domain.com/api/v1/login \
     -H "Content-Type: application/json" \
     -d '{"email": "<EMAIL>", "password": "password"}'
   ```

2. **Use the token:**
   ```bash
   curl https://your-domain.com/api/v1/posts \
     -H "Authorization: Bearer YOUR_TOKEN_HERE"
   ```

## Rate Limiting

- API: 60 requests per minute per user
- AI generation: 5 requests per minute
- Report export: 10 requests per minute

## Monitoring

Access Telescope at `/telescope` (local environment only).

## Troubleshooting

**Queue not processing:**
```bash
php artisan queue:restart
php artisan queue:work redis --sleep=3 --tries=3
```

**Cache issues:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

**Database issues:**
```bash
php artisan migrate:status
php artisan migrate --force
```
