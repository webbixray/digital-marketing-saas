# Digital Marketing SaaS — Deployment Runbook

**Version:** 1.0  
**Applies to:** Digital Marketing SaaS (Laravel 13, PHP 8.4)  
**Owner:** Webbixray Engineering  
**Last updated:** October 2026

---

## 1. Overview

This runbook documents the production deployment process for the Digital Marketing SaaS platform. It covers pre-deployment checks, deployment steps, post-deployment verification, and rollback procedures.

---

## 2. Pre-Deployment Checklist

Complete all items before deploying to production:

- [ ] All CI checks pass (PHP tests, Pint, PHPStan, security audit)
- [ ] Playwright E2E tests pass in the frontend CI workflow
- [ ] Load test smoke run passes against staging
- [ ] `.env.production` configured with all required values (see §4)
- [ ] SSL/TLS certificates provisioned for the production domain
- [ ] Production database provisioned and migrated
- [ ] Redis instance provisioned (queues, cache, sessions)
- [ ] S3 bucket provisioned for file storage and backups
- [ ] Stripe production account configured (products, prices, webhooks)
- [ ] Social platform OAuth apps configured (Facebook, Instagram, X, LinkedIn, TikTok, Pinterest, YouTube)
- [ ] AI provider API keys obtained (OpenAI, Anthropic, Google)
- [ ] Sentry project configured for error tracking
- [ ] DNS A record points to production server IP

---

## 3. Deployment Steps

Deploy using the automated `deploy.sh` script:

```bash
# On the production server:
cd /var/www/digitalmarketingsaas

# Pull latest code
git pull origin main

# Install production dependencies
composer install --no-dev --optimize-autoloader

# Generate application key (first deploy only)
php artisan key:generate --force

# Run database migrations
php artisan migrate --force

# Cache configuration, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart

# Reload PHP-FPM
sudo systemctl reload php8.4-fpm
```

### Manual Deployment (if script is unavailable)

If `deploy.sh` cannot be used, follow the steps above in order. Do not skip caching or queue restart steps.

---

## 4. Production Environment Variables

The `.env` file must contain the following production values. Never commit this file.

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_NAME` | Application name | `Digital Marketing SaaS` |
| `APP_ENV` | Environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |
| `APP_URL` | Production URL | `https://app.yourdomain.com` |
| `APP_KEY` | Application encryption key | `base64:...` |
| `BCRYPT_ROUNDS` | Password hashing cost | `12` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `digitalmarketingsaas` |
| `DB_USERNAME` | Database user | `saas_user` |
| `DB_PASSWORD` | Database password | *(strong)* |
| `REDIS_HOST` | Redis host | `127.0.0.1` |
| `REDIS_PORT` | Redis port | `6379` |
| `REDIS_PASSWORD` | Redis password | *(strong)* |
| `CACHE_DRIVER` | Cache driver | `redis` |
| `SESSION_DRIVER` | Session driver | `redis` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `MAIL_MAILER` | Mail driver | `smtp` |
| `MAIL_HOST` | SMTP host | `smtp.mailgun.org` |
| `MAIL_PORT` | SMTP port | `587` |
| `MAIL_USERNAME` | SMTP username | `postmaster@yourdomain.com` |
| `MAIL_PASSWORD` | SMTP password | *(strong)* |
| `STRIPE_KEY` | Stripe public key | `pk_live_...` |
| `STRIPE_SECRET` | Stripe secret key | `sk_live_...` |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook secret | `whsec_...` |
| `OPENAI_API_KEY` | OpenAI API key | `sk-...` |
| `ANTHROPIC_API_KEY` | Anthropic API key | `sk-ant-...` |
| `GOOGLE_AI_API_KEY` | Google AI API key | `...` |
| `SENTRY_LARAVEL_DSN` | Sentry DSN | `https://...@sentry.io/...` |
| `FILESYSTEM_DISK` | File storage disk | `s3` |
| `AWS_ACCESS_KEY_ID` | AWS access key | `...` |
| `AWS_SECRET_ACCESS_KEY` | AWS secret key | `...` |
| `AWS_DEFAULT_REGION` | AWS region | `us-east-1` |
| `AWS_BUCKET` | S3 bucket name | `saas-production-files` |

Set file permissions: `chmod 600 .env`

---

## 5. Post-Deployment Verification

Verify the deployment succeeded:

1. `https://your-domain.com/up` returns `200 OK`
2. `https://your-domain.com/api/health` returns `{"status":"ok"}`
3. Register a new agency → complete email verification → onboarding
4. Connect a social account → create and publish a post
5. Create a campaign → add posts → view analytics
6. Test billing flow (Stripe test mode)
7. Verify queue workers are processing jobs (`php artisan queue:work` running)
8. Check Horizon dashboard (restrict to admin roles)
9. Monitor Sentry for errors
10. Run a backup test

---

## 6. Rollback Procedure

If a deployment introduces a critical issue:

```bash
cd /var/www/digitalmarketingsaas

# Roll back to the previous commit
git revert --no-commit HEAD~1  # or git checkout <previous-commit>
git commit -m "Rollback deployment"

# Re-run migrations if schema was rolled back
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
php artisan queue:restart
sudo systemctl reload php8.4-fpm
```

For database rollbacks, create a fresh migration rather than rolling back migrations, to preserve data integrity.

---

## 7. Database Backups

- **Frequency:** Daily
- **Retention:** 30 days
- **Location:** S3 bucket
- **Test:** Monthly restore test

Backup command:
```bash
php artisan backup:run
# or via scheduler: php artisan schedule:run
```

---

## 8. Contacts

| Role | Contact |
|------|---------|
| Platform Owner | CEO/Owner |
| Technical Lead | Dev Team Lead |
| DevOps | Infrastructure Team |
| Security | Security Team |
| Stripe Support | Stripe Dashboard → Support |
| Sentry | Sentry.io Project → Alerts |
