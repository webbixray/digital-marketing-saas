# Digital Marketing SaaS - Production Deployment Guide

## Overview

Multi-tenant SaaS platform for digital marketing agencies. AI-powered social media management across Facebook, Instagram, X (Twitter), LinkedIn, TikTok, Pinterest, and YouTube.

## Prerequisites

- Docker Desktop (Windows/macOS) or Docker Engine (Linux)
- Docker Compose v2+
- Git
- Domain name (for production)

## Quick Start (Production)

### 1. Clone and configure

```bash
git clone https://github.com/webbixray/digital-marketing-saas.git
cd digital-marketingsaas
cp env.production.example .env
```

### 2. Configure environment

Edit `.env` with your production values:

```env
APP_NAME="Digital Marketing SaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=db
DB_DATABASE=digitalmarketingsaas
DB_USERNAME=dmsaas
DB_PASSWORD=<REDACTED>

REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Generate a secure key
APP_KEY=base64:your-generated-key-here
```

### 3. Build and start containers

```bash
docker-compose -f docker-compose.prod.yml up -d --build
```

### 4. Run migrations and seeders

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

### 5. Generate application key

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate --force
```

### 6. Optimize

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

### 7. Set up SSL (Let's Encrypt)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo certbot renew --dry-run
```

## Architecture

### Services

| Service | Port | Description |
|---------|------|-------------|
| app | 9000 | PHP-FPM application |
| webserver | 80/443 | Nginx reverse proxy |
| db | 3306 | MySQL 8.0 |
| redis | 6379 | Redis (cache/sessions/queue) |
| queue | - | Laravel queue worker |
| scheduler | - | Laravel task scheduler |

### Docker Compose Files

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Local development |
| `docker-compose.staging.yml` | Staging environment |
| `docker-compose.prod.yml` | Production environment |

## Security

### Implemented

- Multi-tenant isolation (agency_id on all models)
- Role-based access control (Spatie permissions)
- API rate limiting (per-user and per-platform)
- Quota enforcement (plan-based limits)
- Webhook secret validation
- Security headers (CSP, HSTS, X-Frame-Options, etc.)
- Input validation on all endpoints
- CSRF protection
- Encrypted sessions
- SQL injection prevention (Eloquent ORM)
- XSS prevention (Blade templating)

### Production Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database passwords changed
- [ ] Redis password set
- [ ] SSL certificate installed
- [ ] Security headers verified
- [ ] Backups configured
- [ ] Monitoring enabled (Sentry)
- [ ] Log rotation configured

## Monitoring

### Health Checks

```bash
# Application health
curl https://your-domain.com/up

# API status
curl https://your-domain.com/api/v1/status
```

### Logs

```bash
# Application logs
docker-compose -f docker-compose.prod.yml logs -f app

# Nginx logs
docker-compose -f docker-compose.prod.yml logs -f webserver

# Database logs
docker-compose -f docker-compose.prod.yml logs -f db
```

## Backup

### Database Backup

```bash
# Manual backup
docker-compose -f docker-compose.prod.yml exec db mysqldump -u dmsaas -p digitalmarketingsaas > backup.sql

# Automated backup (add to crontab)
0 2 * * * docker-compose -f docker-compose.prod.yml exec db mysqldump -u dmsaas -p digitalmarketingsaas > /backups/backup-$(date +\%Y\%m\%d).sql
```

### Restore

```bash
docker-compose -f docker-compose.prod.yml exec db mysql -u dmsaas -p digitalmarketingsaas < backup.sql
```

## Scaling

### Horizontal Scaling

```bash
# Scale app containers
docker-compose -f docker-compose.prod.yml up -d --scale app=3

# Scale queue workers
docker-compose -f docker-compose.prod.yml up -d --scale queue=5
```

### Database Scaling

- Use RDS Multi-AZ for high availability
- Enable read replicas for read-heavy workloads
- Use connection pooling (ProxySQL)

## Troubleshooting

### Common Issues

**500 Internal Server Error**
```bash
docker-compose -f docker-compose.prod.yml logs app
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
```

**Queue Not Processing**
```bash
docker-compose -f docker-compose.prod.yml logs queue
docker-compose -f docker-compose.prod.yml exec app php artisan queue:restart
```

**Database Connection Failed**
```bash
docker-compose -f docker-compose.prod.yml logs db
docker-compose -f docker-compose.prod.yml exec db mysql -u dmsaas -p
```

## Support

- Documentation: https://your-domain.com/docs
- API Documentation: https://your-domain.com/api/docs
- Support Email: <REDACTED>

## License

MIT License
