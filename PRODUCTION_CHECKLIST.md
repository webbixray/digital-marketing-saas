# Production Deployment Checklist

## Pre-Deployment

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_URL=https://your-domain.com`
- [ ] Generate new `APP_KEY`: `php artisan key:generate --show`
- [ ] Configure MySQL database (MySQL 8.0+)
- [ ] Configure Redis for cache/queue/session
- [ ] Set `SESSION_DRIVER=redis`
- [ ] Set `CACHE_DRIVER=redis`
- [ ] Set `QUEUE_CONNECTION=redis`
- [ ] Configure SMTP (Mailgun/SES/Postmark)
- [ ] Set up Stripe live API keys
- [ ] Configure Sentry DSN for error tracking
- [ ] Set `CORS_ALLOWED_ORIGINS=https://your-domain.com`
- [ ] Configure social media API keys (optional)
- [ ] Set up SSL certificate

## Deployment Steps

```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader

# 2. Run migrations
php artisan migrate --force

# 3. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Build frontend assets
npm ci
npm run build

# 6. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. Restart queue workers
php artisan queue:restart

# 8. Verify health
php artisan health:check
```

## Post-Deployment

- [ ] Run `php artisan health:check`
- [ ] Verify `/up` endpoint returns 200
- [ ] Test login/register flow
- [ ] Verify API endpoints respond correctly
- [ ] Check error tracking in Sentry
- [ ] Verify email delivery
- [ ] Test file uploads
- [ ] Monitor queue workers: `php artisan horizon:status`

## Infrastructure

- [ ] Web server: Nginx with PHP 8.4-FPM
- [ ] Database: MySQL 8.0+ (InnoDB, UTF8MB4)
- [ ] Cache/Queue: Redis 7+
- [ ] Queue worker: Supervisor (for `php artisan queue:work`)
- [ ] Scheduler: Cron entry for `php artisan schedule:run`
- [ ] SSL: Let's Encrypt or commercial certificate
- [ ] CDN: CloudFront/Cloudflare for static assets
- [ ] Backup: Daily database backups (automated)
- [ ] Monitoring: Sentry + Laravel Telescope (dev only)

## Security

- [ ] CSP headers enabled (nonce-based)
- [ ] HSTS enabled (1 year)
- [ ] Rate limiting on all API routes
- [ ] Session encryption enabled
- [ ] Password hashing (bcrypt, 12 rounds)
- [ ] 2FA support available
- [ ] Agency data isolation verified
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] XSS prevention (Blade auto-escaping)
- [ ] CSRF protection on all forms
- [ ] Mass assignment protection ($fillable)
