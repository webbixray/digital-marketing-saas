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
   cd digitalmarkingsaas
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
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS="hello@your-domain.com"
   MAIL_FROM_NAME="Digital Marketing SaaS"

   STRIPE_KEY=pk_live_xxxxxxxxxxxxxxxxxxxxxxxx
   STRIPE_SECRET=sk_live_xxxxxxxxxxxxxxxxxxxxxxxx
   STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxx

   SENTRY_LARAVEL_DSN=https://xxxxxxxxxxxxxxxxxxxxxxxx@sentry.io/xxxxxxx
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
| Process scheduled posts | Every minute | `social:process-scheduled` |
| Retry failed posts | Every 5 minutes | `social:retry-failed` |
| Send email campaigns | Every minute | `email:send-campaigns` |
| Fetch platform metrics | Every hour | `social:fetch-metrics` |
| Run workflow scheduler | Every minute | `workflows:run-scheduler` |

## Stripe Webhook Setup

1. Go to Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://your-domain.com/billing/webhook`
3. Select events:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.paid`
   - `invoice.payment_failed`
4. Copy webhook secret to `STRIPE_WEBHOOK_SECRET`

## Email Configuration

### Recommended: Mailgun (Free tier: 100 emails/month)

1. Sign up at https://www.mailgun.com
2. Add your domain
3. Set environment variables:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailgun.org
   MAIL_PORT=587
   MAIL_USERNAME=postmaster@your-domain.com
   MAIL_PASSWORD=your_mailgun_password
   ```

### Alternative: Amazon SES

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
```

## Sentry Error Tracking (Recommended)

1. Create account at https://sentry.io
2. Create new project → Laravel
3. Copy DSN to `SENTRY_LARAVEL_DSN`

## File Storage

### Local (Default)

```env
FILESYSTEM_DISK=local
```

### Amazon S3 (Recommended for production)

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

## SSL/HTTPS

For production, ensure SSL is configured:

1. Use Cloudflare (free) or Let's Encrypt
2. Set `APP_URL=https://your-domain.com`
3. Set `SESSION_SECURE_COOKIE=true` in `.env`

## Monitoring

### Health Check Endpoint

```
GET /api/health
```

Returns JSON with status of database, cache, queue, and storage.

### Telescope (Local Only)

```bash
php artisan telescope:publish
```

Access at `/telescope` (disabled in production by default).

## Backup Strategy

1. **Database:** Daily automated backups
   ```bash
   mysqldump -u dmsaas -p digitalmarketingsaas > backup_$(date +%Y%m%d).sql
   ```

2. **Files:** Sync to S3
   ```bash
   aws s3 sync storage/app/public s3://your-bucket/backups/
   ```

## Production Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` set to production domain
- [ ] Database migrated and seeded
- [ ] Queue workers running
- [ ] Scheduler running
- [ ] Stripe configured with live keys
- [ ] Email configured (Mailgun/SES)
- [ ] Sentry configured
- [ ] SSL certificate installed
- [ ] Backups scheduled
- [ ] File storage configured (S3 recommended)
- [ ] Webhook endpoints registered
- [ ] API rate limiting enabled
- [ ] Error logging active

## Troubleshooting

### Queue Jobs Not Processing

```bash
# Check queue worker is running
docker-compose ps queue

# Restart queue worker
docker-compose restart queue

# Check failed jobs
php artisan queue:failed
```

### Emails Not Sending

```bash
# Test email configuration
php artisan tinker
Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

### Stripe Webhook Failing

```bash
# List recent webhooks
stripe webhook_endpoints list

# Test webhook locally
stripe listen --forward-to localhost:8000/billing/webhook
```

## Security Recommendations

1. **Use strong passwords** for all services
2. **Enable 2FA** on all admin accounts
3. **Regularly update** dependencies: `composer update && npm update`
4. **Monitor Sentry** for security exceptions
5. **Review access logs** regularly
6. **Encrypt sensitive data** at rest
7. **Use environment variables** for all secrets
8. **Disable debug mode** in production
9. **Rate limit API endpoints**
10. **Validate all user input**
