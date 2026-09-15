# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x | ✅ |

## Reporting a Vulnerability

If you discover a security vulnerability, please report it by emailing **security@your-domain.com**.

Please include:
- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Any suggested fixes

We will acknowledge receipt within 48 hours and aim to provide a fix within 30 days.

## Security Measures

This project implements the following security measures:

- CSRF protection on all forms
- SQL injection prevention via Eloquent ORM
- XSS prevention via Blade templating
- Rate limiting on API endpoints
- Multi-tenancy data isolation
- HMAC-signed unsubscribe URLs
- Encrypted sessions and cookies
- Input validation on all requests
- Authorization policies for all models
