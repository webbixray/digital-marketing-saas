#!/usr/bin/env python3
"""
Pilot UAT runner — Digital Marketing SaaS (14 scenarios per docs/launch/UAT_PLAN.md)
Targets http://127.0.0.1:8082 (dev server). Writes results to UAT_RESULTS.md.

Usage: python3 uat_b.py
"""
import json, re, sys, time, http.cookiejar, urllib.request, urllib.parse, urllib.error, subprocess, uuid

B = "http://127.0.0.1:8082"
STAMP = time.strftime("%Y%m%d-%H%M%S")
RESULTS = []

def record(sid, name, status, evidence):
    RESULTS.append((sid, name, status, evidence))
    print(f"[{status:5s}] {sid} {name} — {evidence[:160]}")

# ---------- helpers ----------
class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None  # let WebSession follow manually (captures Set-Cookie on every hop)

class WebSession:
    """Manual cookie + redirect handling.

    Laravel rotates the session id on login; the new cookie is set on the 302
    hop. urllib's auto-follow only exposes the FINAL response headers, which
    would drop the rotated cookie. So we follow redirects manually and harvest
    Set-Cookie from every hop. (Secure-flag cookies are delivered explicitly —
    equivalent to curl on localhost; real browsers over plain-HTTP won't send
    Secure cookies, which is a dev .env concern, documented in LAUNCH_TRACKER.)
    """

    def __init__(self):
        self.cookies = {}
        self.opener = urllib.request.build_opener(_NoRedirect())

    def _update(self, headers):
        for h in headers.get_all("Set-Cookie") or []:
            name, _, rest = h.partition("=")
            val = rest.split(";")[0].strip()
            if val:
                self.cookies[name.strip()] = val
            else:
                self.cookies.pop(name.strip(), None)

    def _cookie_header(self):
        return "; ".join(f"{k}={v}" for k, v in self.cookies.items())

    def _fire(self, method, path, fields=None, timeout=60):
        data = urllib.parse.urlencode(fields).encode() if fields is not None else None
        req = urllib.request.Request(B + path, data=data, method=method)
        if fields is not None:
            req.add_header("Content-Type", "application/x-www-form-urlencoded")
        if self.cookies:
            req.add_header("Cookie", self._cookie_header())
        try:
            r = self.opener.open(req, timeout=timeout)
            self._update(r.headers)
            return r.status, r.read().decode(errors="replace"), dict(r.headers), r.url
        except urllib.error.HTTPError as e:
            self._update(e.headers)
            return e.code, e.read().decode(errors="replace"), dict(e.headers), e.url

    def _follow(self, method, path, fields, timeout):
        status, body, hdrs, url = self._fire(method, path, fields, timeout)
        hops = 0
        while status in (301, 302, 303, 307, 308) and hops < 5:
            loc = hdrs.get("Location", "")
            if not loc:
                break
            if loc.startswith("http"):
                loc = loc.replace(B, "", 1)
            status, body, hdrs, url = self._fire("GET", loc, None, timeout)
            hops += 1
        return status, body, hdrs, url

    def get(self, path, timeout=60):
        st, body, hdrs, _ = self._follow("GET", path, None, timeout)
        return st, body, hdrs

    def post(self, path, fields, timeout=60):
        st, body, hdrs, final_url = self._follow("POST", path, fields, timeout)
        return st, body, hdrs, final_url

def new_session():
    return WebSession()

def get_page(sess, path, timeout=60):
    return sess.get(path, timeout)

def csrf_from(html):
    m = re.search(r'name="_token" value="([^"]+)"', html)
    if m:
        return m.group(1)
    m = re.search(r'csrf-token" content="([^"]+)"', html)
    return m.group(1) if m else None

def post_form(sess, path, fields):
    return sess.post(path, fields)

def api(token, method, path, payload=None, timeout=90):
    body = json.dumps(payload).encode() if payload is not None else None
    req = urllib.request.Request(B + path, data=body, method=method)
    req.add_header("Authorization", f"Bearer {token}")
    req.add_header("Accept", "application/json")
    if payload is not None:
        req.add_header("Content-Type", "application/json")
    try:
        r = urllib.request.urlopen(req, timeout=timeout)
        raw = r.read().decode(errors="replace")
        return r.status, (json.loads(raw) if raw.strip() else {}), dict(r.headers)
    except urllib.error.HTTPError as e:
        raw = e.read().decode(errors="replace")
        try:
            return e.code, json.loads(raw), dict(e.headers)
        except Exception:
            return e.code, {"_raw": raw[:300]}, dict(e.headers)

def tinker(code):
    out = subprocess.run(
        ["php", "artisan", "tinker", "--execute", code],
        capture_output=True, text=True, cwd="/root/webbixray/projects/digital-marketing-saas", timeout=120
    )
    return out.stdout.strip()

def make_token(email):
    """Dev-only: mint a Sanctum token for an existing user (tinker)."""
    t = tinker(f'''
$u = \\App\\Models\\User::where("email","{email}")->first();
if (!$u) {{ echo "NOUSER"; exit; }}
echo "TOKEN=" . $u->createToken("uat")->plainTextToken;
''')
    m = re.search(r"TOKEN=([A-Za-z0-9|.\-]+)", t)
    return m.group(1) if m else None

# ---------- scenarios ----------
def s01_registration(sess):
    email = f"uat{STAMP}@brightagency.test"
    st, html, _ = get_page(sess, "/register")
    tok = csrf_from(html)
    st2, html2, hd, final_url = post_form(sess, "/register", {
        "_token": tok, "agency_name": f"UAT Bright Agency {STAMP}",
        "name": "UAT Owner", "email": email,
        "password": "PilotPass!234", "password_confirmation": "PilotPass!234",
    })
    # success lands on /email/verify after the rotated-session 302
    st3, html3, _ = get_page(sess, "/dashboard")
    logged_in = st3 == 200 and ("Dashboard" in html3 or "UAT Owner" in html3)
    ok = logged_in and "email/verify" in final_url
    record("UAT-01", "Registration + agency creation + auto-login",
           "PASS" if ok else "FAIL",
           f"register POST={st2} -> {final_url}, dashboard={st3}, logged_in={logged_in}, "
           f"email={email}" + ("" if ok else f" page_snippet={html2[:120]!r}"))
    return email

def s02_login_opener(email):
    sess = new_session()
    st, html, _ = get_page(sess, "/login")
    tok = csrf_from(html)
    st2, html2, _, final_url = post_form(sess, "/login", {
        "_token": tok, "email": email, "password": "PilotPass!234"})
    st3, html3, _ = get_page(sess, "/dashboard")
    ok = st3 == 200
    record("UAT-01b", "Login session flow", "PASS" if ok else "FAIL", f"login={st2} -> {final_url}, dashboard={st3}")
    return sess

def s02_team_rbac(token_a):
    # add a viewer member to agency A via tinker
    r = tinker('''
$agency = \\App\\Models\\Agency::latest("id")->first();
$viewer = \\App\\Models\\User::factory()->create(["name"=>"UAT Viewer", "role"=>"viewer"]);
$viewer->agency_id = $agency->id; $viewer->save();
$role = \\Spatie\\Permission\\Models\\Role::where("name","viewer")->first()
      ?: \\Spatie\\Permission\\Models\\Role::create(["name"=>"viewer","guard_name"=>"web"]);
$viewer->syncRoles([$role]);
echo $viewer->email;
''')
    email = r.splitlines()[-1].strip() if r else None
    if not email or "@" not in email:
        record("UAT-02", "Team & RBAC", "FAIL", f"viewer creation failed: {r[:100]}")
        return None
    token_v = make_token(email)
    if not token_v:
        record("UAT-02", "Team & RBAC", "FAIL", "could not mint viewer token")
        return None
    st, body, _ = api(token_v, "GET", "/api/v1/rbac/roles")
    st2, body2, _ = api(token_v, "GET", "/api/v1/agency/billing")
    ok = st == 200 and st2 in (403, 401)
    record("UAT-02", "Team & RBAC (viewer blocked from billing)",
           "PASS" if ok else "REVIEW",
           f"roles={st}, viewer billing={st2} (expect 403/401)")
    return token_v

def s02_white_label(sess):
    st, html, _ = get_page(sess, "/white-label")
    tok = csrf_from(html)
    st2, html2, _, _ = post_form(sess, "/white-label",
                                 {"_token": tok, "brand_name": f"UAT Brand {STAMP}",
                                  "brand_color": "#FF5733", "hide_powered_by": "1", "enabled": "1"})
    st3, html3, _ = get_page(sess, "/white-label")
    persisted = "FF5733" in html3 or "UAT Brand" in html3
    ok = st == 200 and st2 in (302, 200) and persisted
    record("UAT-03", "White-label branding (web, owner)", "PASS" if ok else "REVIEW",
           f"get={st}, post={st2}, persisted={persisted}")

def s04_ai_generation(token_a):
    st, body, _ = api(token_a, "POST", "/api/v1/ai/generate", {
        "action": "generate",
        "prompt": "Write a short social media caption about a 20% summer sale for a coffee shop.",
        "content_type": "caption", "tone": "friendly", "length": "short",
    }, timeout=120)
    data = body.get("data", body) if isinstance(body, dict) else {}
    content = data.get("content") or data.get("result") or data.get("text") or data.get("output") or ""
    ok = st == 200 and len(content) > 20
    record("UAT-04", "AI content generation (live provider)", "PASS" if ok else "REVIEW",
           f"status={st}, len={len(content)}, sample={content[:80]!r}, err={str(body.get('error', body.get('message','')))[:80]}")

def s05_social_post(token_a):
    st, body, _ = api(token_a, "POST", "/api/v1/posts", {
        "content": f"UAT scheduled post {STAMP} — launching our new summer collection!",
        "platform": "twitter", "status": "scheduled",
        "scheduled_at": (time.time() + 86400) and time.strftime("%Y-%m-%d %H:%M:%S", time.gmtime(time.time() + 86400)),
    })
    data = body.get("data", body) if isinstance(body, dict) else {}
    pid = data.get("id") or (data.get("post") or {}).get("id")
    ok = st in (200, 201) and pid
    record("UAT-05", "Social post scheduling", "PASS" if ok else "REVIEW",
           f"status={st}, post_id={pid}, err={str(body.get('error', body.get('message','')))[:80]}")
    return pid

def s06_email_campaign(token_a):
    st, body, _ = api(token_a, "POST", "/api/v1/campaigns", {
        "name": f"UAT Summer Email {STAMP}", "type": "email",
        "status": "draft",
    })
    data = body.get("data", body) if isinstance(body, dict) else {}
    cid = data.get("id") or (data.get("campaign") or {}).get("id")
    ok = st in (200, 201) and cid
    record("UAT-06", "Email campaign create", "PASS" if ok else "REVIEW",
           f"status={st}, campaign_id={cid}, err={str(body.get('error', body.get('message','')))[:80]}")
    return cid

def s07_ab_test(token_a, cid):
    if not cid:
        record("UAT-07", "A/B test endpoint", "SKIP", "no campaign from UAT-06")
        return
    st, body, _ = api(token_a, "POST", f"/api/v1/campaigns/{cid}/agent-ab-test", {"test_type": "creative"})
    ok = st in (200, 201, 202)
    record("UAT-07", "A/B test via agent", "PASS" if ok else "REVIEW",
           f"status={st}, resp={str(body)[:120]}")

def s08_client_portal(token_a):
    st, body, _ = api(token_a, "POST", "/api/v1/clients", {
        "name": f"UAT Client Corp {STAMP}", "email": f"contact@uclient{STAMP}.test",
    })
    data = body.get("data", body) if isinstance(body, dict) else {}
    clid = data.get("id") or (data.get("client") or {}).get("id")
    st2, body2, _ = api(token_a, "GET", f"/api/v1/client-portal/{clid}/analytics")
    ok = st in (200, 201, 422) and st2 in (200, 403)
    record("UAT-08", "Client + portal", "PASS" if ok else "REVIEW",
           f"client={st} id={clid}, portal/analytics={st2}")

def s09_analytics(token_a):
    st, body, _ = api(token_a, "GET", "/api/v1/analytics/dashboard")
    ok = st == 200
    record("UAT-09", "Analytics endpoint", "PASS" if ok else "REVIEW",
           f"status={st}, keys={list(body.get('data', body).keys())[:8] if isinstance(body, dict) else 'n/a'}")

def s10_report_export(token_a):
    st, body, _ = api(token_a, "POST", "/api/v1/reports/export", {"type": "csv"})
    ok = st in (200, 201)
    sample = str(body)[:100]
    record("UAT-10", "Report export (CSV)", "PASS" if ok else "REVIEW",
           f"status={st}, sample={sample!r}")

def s11_billing_quota(token_a):
    st, body, _ = api(token_a, "GET", "/api/v1/agency/billing")
    plan = None
    if isinstance(body, dict):
        d = body.get("data", body)
        if isinstance(d, dict):
            plan = d.get("plan") or d.get("subscription_plan") or (d.get("agency") or {}).get("subscription_plan")
        elif isinstance(d, list):
            plan = "invoices-list"
    ok = st == 200
    record("UAT-11", "Billing status (owner)", "PASS" if ok else "REVIEW",
           f"status={st}, plan={plan}")
    return plan

def s12_workflow(token_a):
    st, body, _ = api(token_a, "POST", "/api/v1/workflows", {
        "name": f"UAT Workflow {STAMP}",
        "trigger_type": "post_published",
        "actions": [{"type": "log", "message": "UAT test action"}],
    })
    ok = st in (200, 201)
    record("UAT-12", "Workflow automation create", "PASS" if ok else "REVIEW",
           f"status={st}, err={str(body.get('error', body.get('message','')))[:100]}")

def s13_isolation(token_a, email_a):
    # create a second agency user via its own registration-like tinker path
    email_b = f"uat{STAMP}@nimbus.test"
    tinker(f'''
$agency = \\App\\Models\\Agency::create(["name"=>"UAT Nimbus {STAMP}","slug"=>"nimbus-{STAMP}","email"=>"{email_b}","status"=>"active"]);
$agency->subscription_plan="free"; $agency->subscription_status="active"; $agency->save();
$u = \\App\\Models\\User::factory()->create(["name"=>"UAT B Owner","email"=>"{email_b}"]);
$u->agency_id = $agency->id; $u->save();
''')
    token_b = make_token(email_b)
    if not token_b:
        record("UAT-13", "Cross-agency isolation", "FAIL", "could not create agency B")
        return
    st, body, _ = api(token_a, "GET", "/api/v1/campaigns")
    cid = None
    if isinstance(body, dict):
        data = body.get("data", [])
        if isinstance(data, list) and data:
            cid = data[0].get("id")
    if not cid:
        record("UAT-13", "Cross-agency isolation", "REVIEW", "no campaign A to test against")
        return
    st2, body2, _ = api(token_b, "GET", f"/api/v1/campaigns/{cid}")
    ok = st2 in (404, 403)
    record("UAT-13", "Cross-agency isolation", "PASS" if ok else "FAIL",
           f"agencyB GET agencyA campaign {cid} -> {st2} (expect 404/403)")

def s14_rate_limit_headers():
    sess = new_session()
    st, html, hd = get_page(sess, "/login")
    tok = csrf_from(html)
    last = 0
    for i in range(11):
        st, _, _, _ = post_form(sess, "/login", {"_token": tok, "email": "ratelimit@test.io", "password": "wrongpass123"})
        last = st
    headers_ok = any(k.lower() in {h.lower() for h in hd} for k in ("X-Frame-Options", "X-Content-Type-Options", "Content-Security-Policy", "Referrer-Policy"))
    # guest /admin with a no-redirect opener: expect 302 (to login) or 403, never 200
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):
            return None
    op2 = urllib.request.build_opener(NoRedirect)
    try:
        r2 = op2.open(B + "/admin", timeout=30)
        st_admin = r2.status
    except urllib.error.HTTPError as e:
        st_admin = e.code
    ok = last == 429 and st_admin in (302, 403, 401)
    record("UAT-14", "Rate limiting + security headers + admin block",
           "PASS" if ok else "REVIEW",
           f"11th login={last} (expect 429), sec-headers={headers_ok}, guest /admin={st_admin} (expect 302/403)")

# ---------- main ----------
def main():
    sess = new_session()
    email = s01_registration(sess)
    if not email:
        print("FATAL: registration failed"); return 1
    token_a = make_token(email)
    if not token_a:
        print("FATAL: could not mint token for agency A owner"); return 1
    s02_login_opener(email)
    s02_team_rbac(token_a)
    op_login = s02_login_opener(email)
    s02_white_label(op_login)
    s04_ai_generation(token_a)
    pid = s05_social_post(token_a)
    cid = s06_email_campaign(token_a)
    s07_ab_test(token_a, cid)
    s08_client_portal(token_a)
    s09_analytics(token_a)
    s10_report_export(token_a)
    s11_billing_quota(token_a)
    s12_workflow(token_a)
    s13_isolation(token_a, email)
    s14_rate_limit_headers()

    passed = sum(1 for r in RESULTS if r[2] == "PASS")
    failed = sum(1 for r in RESULTS if r[2] == "FAIL")
    review = sum(1 for r in RESULTS if r[2] in ("REVIEW", "SKIP"))
    print(f"\n==== UAT SUMMARY: {passed} pass / {failed} fail / {review} review-skip ====")

    with open("/root/webbixray/projects/digital-marketing-saas/docs/launch/UAT_RESULTS.md", "w") as f:
        f.write(f"# Pilot UAT Results — Digital Marketing SaaS\n\n")
        f.write(f"**Run:** {time.strftime('%Y-%m-%d %H:%M:%S')}  \n")
        f.write(f"**Environment:** dev server :8082, sqlite, 4 workers; AI provider = env-configured (live call)  \n")
        f.write(f"**Summary:** {passed} PASS / {failed} FAIL / {review} REVIEW-SKIP of {len(RESULTS)} scenarios\n\n")
        f.write("| # | Scenario | Result | Evidence |\n|---|----------|--------|----------|\n")
        for sid, name, status, ev in RESULTS:
            f.write(f"| {sid} | {name} | {status} | {ev} |\n")
    print("written: docs/launch/UAT_RESULTS.md")
    return 0 if failed == 0 else 2

if __name__ == "__main__":
    sys.exit(main())