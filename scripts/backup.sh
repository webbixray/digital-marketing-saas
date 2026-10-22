#!/bin/bash
# Database backup script (run via cron daily)
# Usage: ./scripts/backup.sh

set -euo pipefail

BACKUP_DIR="/backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR

# Load environment variables
cd /var/www/digitalmarketingsaas
source .env

# Backup database
echo "Creating database backup..."
mysqldump -u "$DB_USERNAME" \
          -p"$DB_PASSWORD" \
          -h "$DB_HOST" \
          "$DB_DATABASE" | gzip > "$BACKUP_DIR/db.sql.gz"

# Backup storage (excluding logs and cache)
echo "Creating storage backup..."
tar -czf "$BACKUP_DIR/storage.tar.gz" \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/framework/sessions/*' \
    storage/

# Keep only last 7 backups
echo "Cleaning old backups..."
ls -dt /backups/*/ | tail -n +8 | xargs rm -rf 2>/dev/null || true

echo "Backup complete: $BACKUP_DIR"
