# Contributing to Digital Marketing SaaS

Thank you for your interest in contributing! This document outlines the process for contributing to this project.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/digital-marketing-saas.git`
3. Create a feature branch: `git checkout -b feature/your-feature-name`
4. Make your changes
5. Run tests: `php artisan test`
6. Run code style checks: `./vendor/bin/pint --test`
7. Commit your changes
8. Push to your fork
9. Open a Pull Request

## Development Setup

```bash
# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Run tests
php artisan test
```

## Code Standards

- Follow PSR-12 coding standards
- Use Laravel Pint for code style: `./vendor/bin/pint`
- Write tests for new features
- Maintain test coverage above 80%
- Use type hints and return types
- Document all public methods

## Commit Message Format

```
type(scope): description

[optional body]

[optional footer]
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Example:
```
feat(campaigns): add bulk campaign creation

- Added bulk create endpoint
- Added validation for bulk operations
- Added tests for bulk creation

Closes #123
```

## Pull Request Process

1. Update documentation if needed
2. Add tests for new functionality
3. Ensure all tests pass
4. Update CHANGELOG.md
5. Request review from maintainers

## Code of Conduct

Please read our [Code of Conduct](CODE_OF_CONDUCT.md) before contributing.

## Security

If you discover a security vulnerability, please email security@your-domain.com instead of opening an issue.
