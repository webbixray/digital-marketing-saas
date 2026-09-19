# Production Deployment Guide

## Quick Start

```bash
# 1. Clone and configure
git clone <your-repo> /var/www/digitalmarketingsaas
cd /var/www/digitalmarketingsaas
cp docker/scripts/.env.production .env
nano .env  # Fill in all values

# 2. Generate app key
php artisan key:generate

# 3. Start production stack
docker-compose -f docker-compose.prod.yml up -d

# 4. Run migrations
docker exec dmsaas-app php artisan migrate --force

# 5. Build frontend assets
docker exec dmsaas-app npm run build

# 6. Optimize
docker exec dmsaas-app php artisan config:cache
docker exec dmsaas-app php artisan route:cache
docker exec dmsaas-app php artisan view:cache
docker exec dmsaas-app php artisan event:cache

# 7. Start monitoring (optional)
docker-compose -f docker-compose.monitoring.yml up -d
```

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Nginx (80/443)                        │
│                    SSL termination, static files             │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                    PHP-FPM 8.4 (app)                         │
│                   Laravel application                        │
│  ┌─────────┐  ┌──────────┐  ┌────────────┐                 │
│  │  Queue   │  │Scheduler │  │  Worker    │                 │
│  │  Redis   │  │  Cron    │  │  PHP       │                 │
│  └─────────┘  └──────────┘  └────────────┘                 │
└─────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│  MySQL 8.0    │   │  Redis 7      │   │  Prometheus   │
│  (db)         │   │  (redis)      │   │  + Grafana    │
└───────────────┘   └───────────────┘   └───────────────┘
```

## Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `APP_KEY` | Laravel encryption key | ✅ |
| `APP_URL` | Application URL | ✅ |
| `DB_PASSWORD` | MySQL password | ✅ |
| `DB_ROOT_PASSWORD` | MySQL root password | ✅ |
| `REDIS_PASSWORD` | Redis password | ❌ |
| `STRIPE_KEY` | Stripe publishable key | ✅ |
| `STRIPE_SECRET` | Stripe secret key | ✅ |
| `MAIL_HOST` | SMTP host | ✅ |
| `MAIL_USERNAME` | SMTP username | ✅ |
| `MAIL_PASSWORD` | SMTP password | ✅ |
| `AWS_ACCESS_KEY_ID` | S3 access key | ❌ |
| `AWS_SECRET_ACCESS_KEY` | S3 secret key | ❌ |
| `SENTRY_LARAVEL_DSN` | Sentry error tracking | ❌ |

## SSL Certificates

Place certificates in `docker/nginx/ssl/`:
- `cert.pem` — SSL certificate
- `key.pem` — Private key

For Let's Encrypt:
```bash
certbot certonly --standalone -d your-domain.com
cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/nginx/ssl/cert.pem
cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/nginx/ssl/key.pem
```

## Backup Strategy

```bash
# Daily backup (add to cron)
0 2 * * * /var/www/digitalmarketingsaas/scripts/backup.sh

# Manual backup
./scripts/backup.sh

# Restore from backup
gunzip -c /backups/20240115_020000/db.sql.gz | mysql -u dmsaas -p digitalmarketingsaas
```

## Monitoring

Access Grafana at `http://your-server:3000` (default: admin/admin)

Key metrics:
- Request rate and latency
- Error rate
- Database connections
- Queue length
- Memory usage

## Security Checklist

- [ ] SSL/TLS configured
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated
- [ ] Database passwords strong
- [ ] Redis password set
- [ ] File permissions correct (775 for storage/bootstrap/cache)
- [ ] `.env` not in git
- [ ] Security headers configured
- [ ] Rate limiting enabled
- [ ] Backups scheduled
- [ ] Monitoring active
- [ ] Sentry configured (optional)

## Troubleshooting

```bash
# View logs
docker logs dmsaas-app
docker logs dmsaas-nginx
docker logs dmsaas-db

# Check container status
docker-compose -f docker-compose.prod.yml ps

# Restart services
docker-compose -f docker-compose.prod.yml restart

# Run artisan commands
docker exec dmsaas-app php artisan <command>

# Check queue
docker exec dmsaas-app php artisan queue:work
```
