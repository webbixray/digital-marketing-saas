# SOC2 Type II Compliance Documentation
# Digital Marketing SaaS Platform
#
# This document describes the security controls and processes in place to
# meet SOC2 Type II Trust Services Criteria: Security, Availability,
# Processing Integrity, Confidentiality, and Privacy.
#
# Version: 1.0
# Last Updated: October 2026
# Owner: Webbixray Security Team

# ============================================================================
# 1. SECURITY (CC6.1 - Logical and Physical Access Controls)
# ============================================================================

## 1.1 Authentication
- All user access requires email/password authentication with bcrypt hashing
  (cost factor 12).
- Multi-factor authentication (TOTP) is mandatory for owner/admin roles
  and configurable per-agency for other roles.
- Session encryption is enabled (SESSION_ENCRYPT=true).
- Session lifetime is 120 minutes with secure cookie flags.

## 1.2 Authorization
- Role-Based Access Control (RBAC) via Spatie Laravel Permission.
- Role hierarchy: owner > admin > manager > member.
- Resource-level policies for Client, Campaign, Invoice, SocialPost, Report,
  AiProviderKey, ChatChannel, ChatMessage, Team, Feature, Form, ContentTemplate,
  CustomField, LandingPage, Product, ActivityLog, Webhook.
- Route-level authorization middleware (`can:`) applied to all 18+ resource routes.
- Authorization denials are logged to ActivityLog with user, agency, ability,
  model, IP address, and user agent (GateEvaluated event listener).
- Multi-tenant isolation: every query filters by agency_id; no cross-tenant
  data leakage found in security audit.

## 1.3 API Access
- Sanctum token-based authentication for API clients.
- API rate limiting by subscription plan (Free: 60, Starter: 300, Pro: 1200,
  Agency: 3600, Enterprise: unlimited).
- IP-based rate limiting (20 req/min) for unauthenticated requests.
- Rate limit headers (X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After)
  returned on all API responses.

## 1.4 Infrastructure Access
- Production access restricted to VPN/bastion hosts.
- SSH key-based authentication only; no password SSH.
- Infrastructure managed via Docker Compose with non-root containers.
- Database and Redis not exposed publicly; bound to internal network.

# ============================================================================
# 2. AVAILABILITY (A1.1 - Performance Monitoring and Capacity)
# ============================================================================

## 2.1 Monitoring
- Health endpoints: /up, /ready, /live, /api/health.
- Detailed health check includes database, cache, queue status.
- Sentry error tracking configured with release and environment tags.
- Laravel Telescope for debug/request inspection (admin only).
- Laravel Horizon for queue monitoring and job inspection.

## 2.2 Alerting
- Uptime monitoring configured for /up, /api/health, and landing page.
- Alert thresholds: error rate > 1%, response time > 500ms p95.
- Alert channels: Slack, email, SMS (configurable via UPTIME_* env vars).

## 2.3 Capacity Planning
- Queue workers configurable (default 2 workers, scheduler enabled).
- Database connection pool settings configurable.
- Redis memory limit 80% with eviction policy.
- PHP-FPM worker count configurable.

## 2.4 Backup and Recovery
- Database backups: daily, 30-day retention, S3 storage.
- File storage backups: daily, 30-day retention, S3 storage.
- Backup restore test: documented procedure with monthly restore test.
- Full disaster recovery drill: quarterly.
- Read replica configuration available for read scalability.

# ============================================================================
# 3. PROCESSING INTEGRITY (PI1.1 - Complete, Valid, Accurate Processing)
# ============================================================================

## 3.1 Data Validation
- All API input validated via Laravel FormRequest classes.
- Mass assignment protection: all models define $fillable; no $guarded = [].
- File upload validation: MIME type checked via finfo, spoof detection,
  getimagesize content verification, extension blocking (.exe, .php).
- File naming: random 40-character names prevent path traversal.

## 3.2 Error Handling
- Standardized JSON exception responses.
- Global exception handler returns appropriate HTTP status codes.
- Sentry captures unhandled exceptions with stack trace and context.
- Queue job failures logged and moved to dead letter queue after max retries.

## 3.3 Audit Trail
- ActivityLog tracks all create/update/delete/restore operations via observers.
- Authorization denials logged via GateEvaluated event listener.
- Stripe webhook events logged with signature verification.
- AI credit purchases logged with agency_id, amount, Stripe payment ID.

## 3.4 Data Integrity
- Foreign key constraints enforced.
- Database migrations version-controlled and tested.
- Soft deletes used for recoverable data deletion.
- GDPR data export and deletion implemented.

# ============================================================================
# 4. CONFIDENTIALITY (C1.1 - Information Protection)
# ============================================================================

## 4.1 Data Encryption
- Application encryption key (APP_KEY) generated and rotated periodically.
- Session data encrypted (SESSION_ENCRYPT=true).
- TOTP secrets encrypted via Laravel encrypt()/decrypt().
- Database connections support SSL (MYSQL_ATTR_SSL_CA).
- S3 bucket encryption at rest recommended.

## 4.2 Access Control
- Least privilege principle applied to all roles and policies.
- API tokens scoped to authenticated user's agency.
- Admin routes restricted to owner/admin roles.
- Health check detail endpoints restricted to owner/admin.

## 4.3 Data Segregation
- Multi-tenant architecture: every query scoped by agency_id.
- No cross-tenant data leakage (verified in security audit E2E tests).
- Client data isolated per agency.

# ============================================================================
# 5. PRIVACY (P1.1 - Personal Information Processing)
# ============================================================================

## 5.1 Data Collection
- Personal data collected: name, email, phone, company, address.
- Data minimization: only collect data necessary for service delivery.
- Consent tracking implemented for GDPR/CCPA compliance.

## 5.2 Data Subject Rights
- GDPR data export: users can request their data in portable format.
- GDPR data deletion: users can request account and data deletion.
- CCPA opt-out implemented.
- Data retention: configurable retention_days per agency.

## 5.3 Data Retention
- Configurable retention_days per agency (default 365 days).
- Automated retention cleanup via scheduled jobs.
- Data deletion requests processed within 30 days.

## 5.4 Third-Party Processors
- Stripe: payment processing.
- Sentry: error tracking.
- Social platforms (Facebook, Instagram, X, LinkedIn, TikTok, Pinterest,
  YouTube): social media publishing via OAuth.
- AI providers (OpenAI, Anthropic, Google): content generation via API.

# ============================================================================
# 6. CHANGE MANAGEMENT
# ============================================================================

## 6.1 Code Changes
- All code changes via pull request with code review.
- CI pipeline: PHP 8.4 tests, security audit (composer audit, PHPStan),
  Pint code style, frontend unit tests, E2E tests.
- Deployment: automated via deploy.sh with health checks.
- Rollback: git revert + cache clear + service restart.

## 6.2 Infrastructure Changes
- Infrastructure as Code: Docker Compose, Dockerfiles.
- Environment variables: .env.production template, never committed.
- Secret management: .env with 600 permissions; CI secrets via GitHub Secrets.

## 6.3 Incident Management
- Incident severity levels: P1 (Critical) to P4 (Low).
- Incident response: Detection → Triage → Containment → Resolution → Recovery.
- Post-mortem required within 48 hours for P1/P2 incidents.
- Communication plan: customers notified within 1 hour for P1/P2.

# ============================================================================
# 7. SYSTEM OPERATIONS
# ============================================================================

## 7.1 Monitoring and Alerting
- Application logs: storage/logs/laravel.log (daily rotation).
- Nginx logs: /var/log/nginx/ (daily rotation).
- PHP-FPM logs: /var/log/php-fpm/ (daily rotation).
- Supervisor logs: /var/log/supervisord.log (daily rotation).
- Log rotation configured via logrotate.

## 7.2 Patch Management
- Security patches applied within 7 days of release.
- Dependency scanning via composer audit in CI.
- PHPStan static analysis in CI (Level 8, 0 errors).

## 7.3 Capacity Planning
- Queue worker monitoring via Horizon.
- Database connection monitoring.
- Redis memory monitoring (80% threshold).
- Disk space monitoring endpoint (/disk-space, admin only).

# ============================================================================
# 8. RISK ASSESSMENT AND MITIGATION
# ============================================================================

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Data breach | Low | High | Encryption, MFA, RBAC, audit logging |
| Service outage | Medium | High | Health checks, monitoring, backups, DR |
| Data loss | Low | High | Daily backups, 30-day retention, restore tests |
| Insider threat | Low | High | Least privilege, audit logging, MFA |
| Third-party breach | Medium | Medium | Vendor assessment, data minimization |

# ============================================================================
# 9. VENDOR MANAGEMENT
# ============================================================================

| Vendor | Purpose | Data Shared | Assessment |
|--------|---------|-------------|------------|
| Stripe | Payments | Payment tokens | SOC2 certified |
| Sentry | Error tracking | Exception data | SOC2 certified |
| OpenAI/Anthropic/Google | AI generation | Prompts (no PII) | Enterprise terms |
| Meta/TikTok/LinkedIn/etc. | Social publishing | OAuth tokens | Platform terms |

# ============================================================================
# 10. EVIDENCE AND AUDIT TRAIL
# ============================================================================

## 10.1 Logs Retained
- Application logs: 90 days.
- Access logs: 90 days.
- Error logs (Sentry): 90 days.
- Audit log (ActivityLog): 1 year (configurable).
- Authorization denial log: 1 year (configurable).

## 10.2 Reports Available
- SOC2 Type II report (upon request, NDA required).
- Penetration test report (annual, upon request).
- Compliance attestation (annual).

# ============================================================================
# 11. CONTACTS
# ============================================================================

| Role | Contact |
|------|---------|
| Security Officer | security@digitalmarketingsaas.com |
| Compliance Officer | compliance@digitalmarketingsaas.com |
| Support | support@digitalmarketingsaas.com |
| Data Protection Officer | dpo@digitalmarketingsaas.com |
