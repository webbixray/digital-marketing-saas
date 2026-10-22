# Digital Marketing SaaS — Backup & Restore Procedures

**Version:** 1.0  
**Applies to:** Digital Marketing SaaS production platform  
**Owner:** Webbixray DevOps  
**Last updated:** October 2026

---

## 1. Backup Strategy

| Data | Frequency | Retention | Location | Method |
|------|-----------|-----------|----------|--------|
| MySQL database | Daily | 30 days | S3 | `mysqldump` + `aws s3 cp` |
| File storage (`storage/app/public`) | Daily | 30 days | S3 | `aws s3 sync` |
| Redis | Not backed up (ephemeral) | — | — | — |
| Code | Per commit | Permanent | GitHub | `git push` |
| Environment (`.env`) | On change | Permanent | Secrets manager / encrypted | Manual |

---

## 2. Database Backup

### Automated Daily Backup

The Laravel scheduler runs `php artisan backup:run` daily at 02:00 UTC. Verify the scheduler is configured:

```cron
* * * * * cd /var/www/digitalmarketingsaas && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Backup

```bash
cd /var/www/digitalmarketingsaas

# Create database dump
mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > backup-$(date +%Y%m%d-%H%M%S).sql

# Upload to S3
aws s3 cp backup-*.sql s3://saas-production-backups/database/

# Clean up local copy
rm backup-*.sql
```

### Verify Backup

```bash
# Download and verify the most recent backup
aws s3 cp s3://saas-production-backups/database/latest.sql /tmp/
mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < /tmp/latest.sql
```

---

## 3. File Storage Backup

### Automated Daily Backup

Files uploaded to `storage/app/public` are synced daily to S3:

```bash
aws s3 sync /var/www/digitalmarketingsaas/storage/app/public \
  s3://saas-production-backups/files/ \
  --delete
```

### Manual Backup

```bash
cd /var/www/digitalmarketingsaas

# Sync files to S3
aws s3 sync storage/app/public s3://saas-production-backups/files/

# Verify file count
aws s3 ls s3://saas-production-backups/files/ --recursive | wc -l
```

---

## 4. Restore Procedures

### 4.1 Restore Database from Backup

**Warning:** This will overwrite the current database. Only perform during a maintenance window.

```bash
cd /var/www/digitalmarketingsaas

# Download backup from S3
aws s3 cp s3://saas-production-backups/database/backup-YYYYMMDD-HHMMSS.sql /tmp/

# Put application in maintenance mode
php artisan down

# Restore database
mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < /tmp/backup-YYYYMMDD-HHMMSS.sql

# Run any pending migrations
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Bring application back online
php artisan up
```

### 4.2 Restore File Storage from Backup

```bash
cd /var/www/digitalmarketingsaas

# Sync files from S3 back to local storage
aws s3 sync s3://saas-production-backups/files/ storage/app/public/

# Set correct permissions
chown -R www-data:www-data storage/app/public
chmod -R 755 storage/app/public
```

### 4.3 Full Disaster Recovery

If the entire production environment is lost:

1. **Provision new infrastructure** (server, database, Redis, S3 bucket)
2. **Restore code** — `git clone` from GitHub, checkout the tagged release
3. **Configure environment** — Create `.env` with production values
4. **Install dependencies** — `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`
5. **Generate key** — `php artisan key:generate`
6. **Run migrations** — `php artisan migrate --force`
7. **Restore database** — Follow §4.1
8. **Restore files** — Follow §4.2
9. **Configure caching** — `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`
10. **Start services** — Queue workers, scheduler, PHP-FPM, Nginx
11. **Update DNS** — Point domain to new server IP
12. **Verify** — Follow deployment runbook §5

---

## 5. Backup Verification Schedule

| Check | Frequency | Method | Owner |
|-------|-----------|--------|-------|
| Backup completed successfully | Daily | Check S3 bucket for new files | DevOps |
| Backup integrity verified | Weekly | Download and validate backup | DevOps |
| Restore test | Monthly | Restore to staging environment | DevOps |
| Full disaster recovery drill | Quarterly | Full restore to clean environment | Engineering |

---

## 6. Retention & Cleanup

- **Database backups:** 30 days retention, then deleted from S3
- **File backups:** 30 days retention, then deleted from S3
- **Code:** Permanent in GitHub
- **`.env`:** Permanent in secrets manager

Cleanup script (run weekly):
```bash
# Delete backups older than 30 days
aws s3 ls s3://saas-production-backups/database/ | while read -r line; do
  createDate=$(echo $line | awk '{print $1" "$2}')
  createDate=$(date -d "$createDate" +%s)
  olderThan=$(date -d "-30 days" +%s)
  if [[ $createDate -lt $olderThan ]]; then
    fileName=$(echo $line | awk '{print $4}')
    if [[ $fileName != "" ]]; then
      aws s3 rm s3://saas-production-backups/database/$fileName
    fi
  fi
done
```

---

## 7. Monitoring & Alerts

Configure alerts for:

- Backup job failure (Sentry or monitoring)
- S3 bucket backup size not increasing for 24+ hours
- Restore test failure
- Database backup older than 24 hours

---

## 8. Contacts

| Role | Contact |
|------|---------|
| DevOps Lead | Infrastructure Team |
| Database Administrator | DevOps Team |
| CTO | CTO/Owner |
