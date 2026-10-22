# Digital Marketing SaaS — Incident Response Runbook

**Version:** 1.0  
**Applies to:** Digital Marketing SaaS production platform  
**Owner:** Webbixray Engineering + Security  
**Last updated:** October 2026

---

## 1. Purpose

This runbook defines the process for responding to security incidents, production outages, and data integrity events affecting the Digital Marketing SaaS platform.

---

## 2. Incident Severity Levels

| Severity | Definition | Response Time | Escalation |
|----------|-----------|---------------|------------|
| **P1 — Critical** | Complete service outage, data breach, or security vulnerability being actively exploited | 15 min | CTO + Security Team + CEO |
| **P2 — High** | Major feature broken, partial outage, or security vulnerability not actively exploited | 1 hour | CTO + Security Team |
| **P3 — Medium** | Degraded performance, minor feature broken, or non-critical bug | 4 hours | Tech Lead |
| **P4 — Low** | Cosmetic issue, documentation gap, or enhancement request | 1 week | Engineering |

---

## 3. Incident Response Process

### Phase 1: Detection & Triage

1. **Detect** — Incident identified via Sentry alert, monitoring, customer report, or internal observation.
2. **Triage** — Assign severity level based on impact and urgency.
3. **Declare** — Open an incident channel (Slack/email) and assign an Incident Commander (IC).
4. **Log** — Record start time, severity, affected systems, and initial observations.

### Phase 2: Containment

1. **Isolate** — If security-related, revoke affected credentials, block malicious IPs, or take the affected service offline.
2. **Preserve evidence** — Do not delete logs or modify affected systems before evidence is captured.
3. **Stabilize** — Apply temporary mitigations to restore partial or full service.

### Phase 3: Investigation & Resolution

1. **Investigate** — Identify root cause using logs, Sentry traces, database queries, and code review.
2. **Fix** — Implement and test a fix in a staging environment.
3. **Deploy** — Deploy the fix to production following the deployment runbook.
4. **Verify** — Confirm the fix resolves the issue and no new issues are introduced.

### Phase 4: Recovery & Post-Mortem

1. **Recover** — Restore full service and confirm all systems are operational.
2. **Post-mortem** — Within 48 hours, document: timeline, root cause, impact, corrective actions, and lessons learned.
3. **Update** — Apply corrective actions (code fixes, process changes, monitoring improvements).

---

## 4. Security Incident Scenarios

### 4.1 Unauthorized Agency Data Access (IDOR / Cross-Tenant)

**Symptoms:** User can view or modify data belonging to another agency.

**Immediate actions:**
1. Revoke the affected user's access tokens.
2. Audit authorization logs for affected agencies.
3. Deploy the authorization fix (see Phase 1 remediation — `$this->authorize()` on all controllers).
4. Notify affected agencies if data was exposed.

**Notification:** GDPR requires notification to supervisory authorities within 72 hours of becoming aware of a personal data breach.

### 4.2 Stripe Webhook Compromise

**Symptoms:** Unauthorized credit purchases, fraudulent invoices.

**Immediate actions:**
1. Rotate Stripe webhook secret in Stripe Dashboard and `.env`.
2. Audit `AiCreditPurchase` and `Invoice` records for anomalous activity.
3. Refund fraudulent transactions via Stripe Dashboard.
4. Deploy rate limiting on webhook endpoints if missing.

### 4.3 AI Provider Key Exposure (BYOK)

**Symptoms:** Unexpected AI usage, high costs, unauthorized provider access.

**Immediate actions:**
1. Rotate the exposed provider API key in the provider dashboard.
2. Delete and re-add the BYOK key in the platform (`AiProviderKey`).
3. Audit `AiCreditPurchase` and `AiAuditLog` for unauthorized usage.
4. Notify affected agencies of potential key exposure.

### 4.4 File Upload Vulnerability

**Symptoms:** Malicious file executed, server compromise.

**Immediate actions:**
1. Isolate the affected server from the network.
2. Remove malicious files from the `storage/app/public` directory.
3. Scan all uploaded files with ClamAV or equivalent.
4. Deploy MIME validation and `getimagesize()` checks (see Phase 2 remediation).

---

## 5. Production Outage Scenarios

### 5.1 Database Unavailable

**Symptoms:** 500 errors on all page loads, database connection exceptions in logs.

**Immediate actions:**
1. Check database service status (`systemctl status mysql`).
2. Check connection pool and max connections.
3. Restart database if unresponsive.
4. Verify application can reconnect.

### 5.2 Redis Unavailable (Queues / Cache / Sessions)

**Symptoms:** Jobs not processing, sessions not persisting, cache misses.

**Immediate actions:**
1. Check Redis service status (`systemctl status redis`).
2. Restart Redis if unresponsive.
3. Verify queue workers are running (`php artisan queue:work`).

### 5.3 Queue Worker Down

**Symptoms:** Jobs piling up, AI generation not completing, emails not sending.

**Immediate actions:**
1. Check worker status (`php artisan queue:work` process).
2. Restart workers (`php artisan queue:restart`).
3. Check for failed jobs (`php artisan queue:failed`).
4. Retry failed jobs (`php artisan queue:retry all`).

---

## 6. Communication Plan

| Audience | Channel | When | Owner |
|----------|---------|------|-------|
| Affected customers | Email / In-app notification | P1/P2, within 1 hour | Customer Success |
| All customers | Status page / Email | P1, within 2 hours | Customer Success |
| Engineering team | Slack incident channel | Immediately | Incident Commander |
| Executive team | Email | P1/P2 | CTO |
| Regulatory bodies | Formal notice | GDPR: within 72 hours | Legal / Security |

---

## 7. Escalation Contacts

| Role | Contact | Availability |
|------|---------|--------------|
| Incident Commander | Tech Lead | 24/7 during incident |
| Security Lead | Security Team | 24/7 during incident |
| CTO | CTO/Owner | P1/P2 escalation |
| CEO | CEO/Owner | P1 escalation, customer notification |
| Infrastructure | DevOps Team | During business hours + on-call |

---

## 8. Post-Incident Checklist

- [ ] Timeline documented (detection → containment → resolution → recovery)
- [ ] Root cause identified
- [ ] Impact assessed (users affected, data exposed, duration)
- [ ] Corrective actions assigned with owners and due dates
- [ ] Monitoring/alerting gaps identified and addressed
- [ ] Post-mortem shared with engineering and leadership
- [ ] Customer notifications sent (if applicable)
- [ ] Regulatory notifications filed (if GDPR applies)
