# Digital Marketing SaaS

Enterprise-grade multi-tenant digital marketing platform built with Laravel 13. Empower agencies to manage social media, email campaigns, AI-driven content, workflow automation, and client billing — all from a single dashboard.

![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4)
![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20)
![License](https://img.shields.io/badge/License-MIT-green)
![Tests](https://img.shields.io/badge/Tests-912%20passing-brightgreen)

## Features

- **Multi-Tenancy** — Agency/workspace scoping with isolated data, permissions, and white-labeling
- **Social Media Management** — Schedule, publish, and analyze posts across Twitter/X, Facebook, Instagram, LinkedIn, and TikTok
- **Email Marketing** — Campaign builder with templates, recipient tracking, and delivery analytics
- **AI Content Generation** — Multi-provider gateway (OpenAI, Anthropic, Google) for captions, images, and full campaigns
- **AI Agent Orchestration** — Autonomous agents for lead generation, weekly reports, social strategy, and security audits
- **Workflow Automation** — Visual drag-and-drop builder with triggers, conditions, and actions
- **Analytics & Reports** — Custom dashboards with PDF, CSV, and Excel export
- **Client Management (CRM)** — Tagging, custom fields, activity feeds, and consent tracking
- **Invoicing & Billing** — Stripe integration with subscription billing and webhook handling
- **Media Library** — Upload, organize, and serve assets with GDPR-compliant storage
- **White-Labeling** — Custom branding, domains, and CSS per agency
- **Team Collaboration** — Comments, mentions, activity feeds, and role-based access control
- **GDPR Compliance** — Data export, deletion requests, and consent management built-in
- **Telegram Bot** — AI assistant accessible via Telegram for on-the-go management

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.4, Laravel 13 |
| **Frontend** | AdminLTE 3.2, Bootstrap 4, Chart.js, Vite |
| **Database** | SQLite (dev), MySQL (production) |
| **Queue** | Redis (recommended) / Database |
| **Cache** | Redis / File |
| **AI** | OpenAI, Anthropic Claude, Google AI |
| **Billing** | Stripe |
| **Permissions** | Spatie Laravel-Permission |

## Installation

### Prerequisites

- PHP 8.4 or higher
- Composer 2.x
- Node.js 18+ and npm
- SQLite (for development) or MySQL 8.0+ (for production)
- Redis (recommended for queues and cache)

### Quick Start

```bash
# Clone the repository
git clone https://github.com/webbixray/digital-marketing-saas.git
cd digital-marketing-saas

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Seed demo data (optional)
php artisan db:seed --force

# Build frontend assets
npm run build

# Start development server
php artisan serve
```

The application will be available at `http://localhost:8000`.

### Demo Credentials

After seeding, you can log in with:

| Role | Email | Password |
|------|-------|----------|
| Agency Owner | owner@agency.com | password123 |
| Manager | manager@agency.com | password123 |
| Staff | staff@agency.com | password123 |
| Client | client@agency.com | password123 |

## Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure the following:

```env
# Application
APP_NAME="Digital Marketing SaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (SQLite for dev, MySQL for production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Session (Redis recommended)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Mail (SMTP or API service)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-domain.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Stripe Billing
STRIPE_KEY=pk_live_xxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_CURRENCY=usd

# AI Providers
OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_ORGANIZATION=org-xxxxxxxxxxxxxxxx
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxx
GOOGLE_AI_API_KEY=xxxxxxxxxxxxxxxx

# Telegram Bot (optional)
TELEGRAM_BOT_TOKEN=123456:ABC-xxxxxxxx
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_ENABLED=true

# Twitter/X API
TWITTER_API_KEY=xxxxxxxx
TWITTER_API_SECRET=xxxxxxxx
TWITTER_ACCESS_TOKEN=xxxxxxxx
TWITTER_ACCESS_SECRET=xxxxxxxx
TWITTER_BEARER_TOKEN=xxxxxxxx
```

### File Storage

Configure your filesystem disk in `config/filesystems.php`. For production, use S3 or compatible:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

### Queue Worker

Start the queue worker for background jobs:

```bash
php artisan queue:work redis --queue=default,ai,reports --sleep=3 --tries=3
```

### Scheduler

Add the following cron entry for the task scheduler:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

The test suite uses SQLite in-memory with the `RefreshDatabase` trait for fast, isolated tests.

```bash
# Run all tests
php artisan test

# Run with PHPUnit directly
php vendor/bin/phpunit

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage (requires Xdebug or PCOV)
php artisan test --coverage

# Run specific test file
php artisan test tests/Unit/Services/WorkflowEngineTest.php
```

### Test Structure

```
tests/
├── TestCase.php              # Base test case
├── Unit/
│   ├── Services/             # Service layer tests
│   ├── Models/               # Model tests
│   ├── Requests/             # Form request validation tests
│   ├── Cache/                # Cache tests
│   ├── Queue/                # Queue tests
│   ├── Observers/            # Model observer tests
│   ├── Notifications/        # Notification tests
│   └── Console/              # Artisan command tests
└── Feature/
    └── (Feature tests)
```

## Contribution Guidelines

We welcome contributions! Please follow these guidelines:

### Getting Started

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes using [Conventional Commits](.github/CONVENTIONAL_COMMITS.md)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow **PSR-12** coding style (enforced by Laravel Pint)
- Use **type hints** and **return types** everywhere
- Write **descriptive variable and function names**
- Keep methods **short and focused** (single responsibility)
- Add **PHPDoc blocks** for public APIs

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### Commit Messages

We use [Conventional Commits](.github/CONVENTIONAL_COMMITS.md):

```
feat: add AI content generation endpoint
fix: resolve agency_id scoping in API routes
docs: update installation instructions
test: add coverage for WorkflowEngine
refactor: extract Stripe gateway into service
```

### Pull Request Process

1. Update documentation for any changed functionality
2. Add tests for new features
3. Ensure all tests pass (`php artisan test`)
4. Ensure code style passes (`./vendor/bin/pint --test`)
5. Request review from at least one maintainer

### Security

- Never commit credentials or API keys
- Report security vulnerabilities to security@your-domain.com
- See [Security Audit Report](security_audit_report.md) for known issues

## License

This project is licensed under the [MIT License](LICENSE).

---

*Built with ❤️ by the Digital Marketing SaaS team.*
