# Digital Marketing SaaS — Monitoring & Alerting Runbook

**Version:** 1.0  
**Applies to:** Digital Marketing SaaS production platform  
**Owner:** Webbixray DevOps  
**Last updated:** October 2026

---

## 1. Monitoring Stack

| Component | Purpose | Location |
|-----------|---------|----------|
| Sentry | Error tracking & exception monitoring | sentry.io |
| Laravel Telescope | Debug & request inspection (admin only) | `/telescope` |
| Laravel Horizon | Queue monitoring & job inspection | `/horizon` |
| Health endpoints | Liveness/readiness probes | `/up`, `/api/health` |
| Nginx logs | Access & error logs | `/var/log/nginx/` |
| PHP-FPM logs | PHP errors | `/var/log/php-fpm/` |
| Application logs | Laravel logs | `storage/logs/laravel.log` |

---

## 2. Health Endpoints

| Endpoint | Purpose | Auth | Expected Response |
|----------|---------|------|-------------------|
| `GET /up` | Liveness probe (load balancer) | Public | `200 OK` |
| `GET /ready` | Readiness probe (Kubernetes) | Public | `200 OK` |
| `GET /live` | Liveness probe | Public | `200 OK` |
| `GET /api/health` | Detailed health (DB, cache, queue) | Public | `{"status":"ok"}` |
| `GET /disk-space` | Disk usage | Auth + owner/admin | JSON with free/total bytes |
| `GET /queue-status` | Queue depth | Auth + owner/admin | JSON queue stats |

---

## 3. Key Metrics to Watch

| Metric | Warning Threshold | Critical Threshold |
|--------|------------------|-------------------|
| Queue latency | > 30 seconds | > 5 minutes |
| API p95 response time | > 500ms | > 2 seconds |
| Error rate | > 0.1% | > 1% |
| Database connections | > 80% of max | > 95% of max |
| Redis memory | > 80% | > 95% |
| Disk usage | > 80% | > 95% |
| Failed jobs | > 10 | > 100 |

---

## 4. Alert Configuration

### Sentry Alerts

Configure in Sentry project settings:

- **Issue alert:** New issue created → notify #alerts channel
- **Release alert:** New errors in release → notify #alerts channel
- **Spike alert:** Sudden increase in error volume → notify #alerts channel

### Application Log Alerts

Monitor `storage/logs/laravel.log` for:

- `ERROR` level logs → investigate within 1 hour
- `CRITICAL` level logs → investigate within 15 minutes
- `Emergency` level logs → page on-call engineer

### Queue Monitoring

Check Horizon dashboard (`/horizon`, restricted to admin):

- Failed jobs count
- Job processing time
- Worker count and status

---

## 5. Incident Response

When an alert fires:

1. **Acknowledge** the alert in the monitoring tool.
2. **Assess** severity using the Incident Response Runbook severity matrix.
3. **Investigate** using logs, Sentry, Horizon, and health endpoints.
4. **Mitigate** — apply temporary fix or rollback if needed.
5. **Resolve** — deploy permanent fix.
6. **Document** — create post-mortem within 48 hours.

---

## 6. Log Locations

| Log Type | Path | Rotation |
|----------|------|----------|
| Application | `storage/logs/laravel.log` | Daily (Laravel log rotation) |
| Nginx access | `/var/log/nginx/access.log` | Daily (logrotate) |
| Nginx error | `/var/log/nginx/error.log` | Daily (logrotate) |
| PHP-FPM | `/var/log/php-fpm/error.log` | Daily (logrotate) |
| Supervisor | `/var/log/supervisord.log` | Daily (logrotate) |

---

## 7. Log Rotation Configuration

Ensure `logrotate` is configured for Nginx and PHP-FPM logs:

```bash
/etc/logrotate.d/nginx
/var/log/nginx/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 $(cat /var/run/nginx.pid)
    endscript
}
```

---

## 8. Contacts

| Role | Contact |
|------|---------|
| DevOps On-Call | Infrastructure Team |
| CTO | CTO/Owner |
| Security Lead | Security Team |
