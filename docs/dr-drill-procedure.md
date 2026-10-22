# Disaster Recovery Drill Procedure
# Digital Marketing SaaS Platform
#
# Version: 1.0
# Last Updated: October 2026
# Owner: Webbixray DevOps Team
#
# This document outlines the quarterly disaster recovery drill procedure.
# The drill validates that the platform can be recovered from a complete
# infrastructure loss within the defined Recovery Time Objective (RTO).

# ============================================================================
# 1. OBJECTIVES
# ============================================================================

- Validate that backups are restorable.
- Validate that the deployment process works from scratch.
- Validate that all services (app, database, Redis, queue, scheduler) start.
- Validate that critical user journeys function after recovery.
- Measure actual Recovery Time Objective (RTO) and Recovery Point Objective (RPO).
- Identify gaps and update this document accordingly.

# ============================================================================
# 2. RTO AND RPO TARGETS
# ============================================================================

| Metric | Target | Measurement |
|--------|--------|-------------|
| Recovery Time Objective (RTO) | 4 hours | Time from disaster declaration to service availability |
| Recovery Point Objective (RPO) | 24 hours | Maximum data loss measured from last backup |

# ============================================================================
# 3. DRILL SCHEDULE
# ============================================================================

| Drill Type | Frequency | Owner |
|------------|-----------|-------|
| Backup restore test | Monthly | DevOps |
| Full disaster recovery drill | Quarterly | DevOps + Engineering |
| Tabletop exercise | Semi-annually | Security + Engineering |

# ============================================================================
# 4. PRE-DRILL CHECKLIST
# ============================================================================

- [ ] Schedule drill with engineering and support teams.
- [ ] Notify support team of planned drill (no customer impact expected).
- [ ] Verify latest backup exists in S3 (within 24 hours).
- [ ] Verify GitHub repository is accessible and main branch is stable.
- [ ] Prepare a clean staging environment (isolated from production).
- [ ] Prepare drill execution log (this document).

# ============================================================================
# 5. DRILL EXECUTION STEPS
# ============================================================================

## Step 1: Declare Disaster (T+0)
- Post incident declaration in #incidents Slack channel.
- Record start time: ___________

## Step 2: Provision Infrastructure
- Provision a clean server (or use staging environment).
- Install Docker and Docker Compose.
- Clone repository: `git clone https://github.com/webbixray/digital-marketing-saas.git`
- Checkout latest stable tag: `git checkout vX.Y.Z`

## Step 3: Restore Database from Backup
- Download latest database backup from S3:
  `aws s3 cp s3://saas-production-backups/database/latest.sql /tmp/`
- Create MySQL database and user.
- Restore: `mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < /tmp/latest.sql`
- Verify row counts match production (within RPO).

## Step 4: Restore File Storage
- Download latest file backup from S3:
  `aws s3 sync s3://saas-production-backups/files/ storage/app/public/`
- Set permissions: `chown -R www-data:www-data storage/app/public`

## Step 5: Configure Application
- Copy .env.production to .env.
- Fill in production values (APP_KEY, DB credentials, Redis, Stripe, S3, AI keys).
- Generate application key: `php artisan key:generate`
- Install dependencies: `composer install --no-dev --optimize-autoloader`
- Install frontend: `npm ci && npm run build`
- Run migrations: `php artisan migrate --force`
- Cache config: `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Step 6: Start Services
- Start queue workers: `php artisan queue:work redis --queue=default,ai,reports --sleep=3 --tries=3 &`
- Start scheduler: `php artisan schedule:work &`
- Start PHP-FPM and Nginx (or use Docker Compose).

## Step 7: Validate Critical User Journeys
Execute the following smoke tests:

### Authentication
- [ ] Register a new agency.
- [ ] Verify email received.
- [ ] Log in with new credentials.
- [ ] Log in with existing test user.

### Core Features
- [ ] Create a social media post.
- [ ] Create a campaign.
- [ ] Create an invoice.
- [ ] View analytics dashboard.
- [ ] Generate AI content.

### API
- [ ] GET /api/health returns 200.
- [ ] GET /api/v1/dashboard returns 200 with auth.
- [ ] POST /api/v1/posts returns 201 with auth.

## Step 8: Measure RTO/RPO
- End time: ___________
- Actual RTO: ___________ (Target: < 4 hours)
- Actual RPO: ___________ (Target: < 24 hours)
- Data loss assessment: ___________

## Step 9: Document Findings
- Issues encountered: ___________
- Workarounds applied: ___________
- Gaps identified: ___________
- Action items: ___________

# ============================================================================
# 6. POST-DRILL ACTIONS
# ============================================================================

- [ ] Update this runbook with lessons learned.
- [ ] Create Jira tickets for identified gaps.
- [ ] Update Recovery Time Objective if actual exceeded target.
- [ ] Communicate results to engineering and leadership.
- [ ] Schedule next quarterly drill.

# ============================================================================
# 7. ROLLBACK PROCEDURE
# ============================================================================

If the drill reveals critical issues:

1. Stop all services: `docker-compose down` (or kill processes).
2. Revert to previous known-good backup.
3. Re-deploy previous application version.
4. Re-run smoke tests.
5. Declare recovery or extend incident.

# ============================================================================
# 8. CONTACTS
# ============================================================================

| Role | Contact | Slack |
|------|---------|-------|
| DevOps Lead | devops@digitalmarketingsaas.com | @devops-lead |
| CTO | cto@digitalmarketingsaas.com | @cto |
| Security Officer | security@digitalmarketingsaas.com | @security |
