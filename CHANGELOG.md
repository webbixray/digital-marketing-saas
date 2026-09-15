# Changelog

All notable changes to the **Digital Marketing SaaS Platform** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Version and changelog system with `VersionService`
- `version:bump` Artisan command for semver bumps
- In-app changelog viewer at `/changelog`
- API endpoint `/api/version` for version checks
- Conventional commits guide for contributors
- Auto-generated release notes from commit messages
- Production deployment configuration (Docker Compose, DEPLOYMENT.md)
- Production launch checklist (PRODUCTION_CHECKLIST.md)
- Sentry error tracking integration
- Stripe webhook handling and subscription management
- Email tracking (opens, clicks) with HMAC-signed unsubscribe URLs
- Unit tests for QuotaService, AgentCostTracker, TrackingService
- Mail configuration tests
- Queue health check tests
- GitHub issue templates (bug report, feature request)
- Security policy and contributing guidelines
- Code of conduct
- MIT License

### Changed
- Migrated inline scripts to unified.js utilities (vanilla JS, no jQuery)
- Improved HandlesErrors trait to return action results directly
- Updated exception handler to report to Sentry with request context
- Migrated multiple controllers to use HandlesErrors trait
- Updated docker-compose.yml with queue workers and scheduler

### Fixed
- Fixed email template edit blade syntax (missing quotes in route call)
- Fixed RegisterController success redirect URL
- Fixed TrackingService to handle base64 encoded URLs properly

### Security
- Added CSRF protection on all forms (104 directives)
- Added per-user rate limiting on API routes
- Added HMAC-SHA256 signed unsubscribe URLs
- Added input validation on all requests
- Added authorization policies for all models

---

## [1.0.0] - 2026-09-05

### Added
- **Platform Launch** — Genesis release
- **Dashboard** — Stats cards, quota usage, activity feed, quick actions
- **Social Media** — Connect accounts, create posts, schedule, publish, retry
- **Campaigns** — Create campaigns, associate clients, change status
- **Clients** — Full CRM with search, filter, CRUD
- **Invoices** — Create, mark paid, edit, delete with line items
- **AI Content** — Generate posts, captions, hashtags, headlines, ad copy
- **Workflows** — Automation rules with triggers and actions
- **Webhooks** — Register endpoints, HMAC-signed payloads
- **Forms** — Build forms, public render, submission tracking
- **Landing Pages** — Create pages with custom colors, CTA, public render
- **Activity Log** — Audit trail with user attribution
- **Search** — Global search across all modules
- **API** — RESTful API with Sanctum auth, v1 endpoints
- **Testing** — 138 tests (Unit, Feature, UAT, Security)
- **Demo Data** — Realistic agency dataset with login credentials

### Security
- Multi-tenant data isolation (agency_id scoping)
- CSRF protection on all forms
- RBAC with Spatie Permission
- Webhook HMAC-SHA256 signing
- Input validation via Form Requests
