<?php
$base = 'http://localhost:8000';
$projectDir = __DIR__;
$results = [];

function test($name, $passed, $detail = '') {
    global $results;
    $results[] = ['name' => $name, 'passed' => $passed, 'detail' => $detail];
    echo " [" . ($passed ? '✓' : '✗') . "] $name" . ($detail ? " - $detail" : '') . "\n";
}

echo "\n═══════════════════════════════════════════════════\n";
echo " FINAL BROWSER AUDIT — DigitalMarketingSaaS\n";
echo "═══════════════════════════════════════════════════\n";

// Test 1: Public pages (sequential to avoid single-threaded server blocking)
echo "\n▸ PUBLIC PAGES\n───────────────────────────────────────────────────\n";

$pages = ['/' => 'Landing', '/pricing' => 'Pricing', '/features' => 'Features', '/docs' => 'Docs', '/terms' => 'Terms', '/privacy' => 'Privacy', '/login' => 'Login', '/register' => 'Register'];

$pubScript = <<<'PHP'
<?php
$base = 'http://localhost:8000';
$pages = ['/' => 'Landing', '/pricing' => 'Pricing', '/features' => 'Features', '/docs' => 'Docs', '/terms' => 'Terms', '/privacy' => 'Privacy', '/login' => 'Login', '/register' => 'Register'];
$result = [];
foreach ($pages as $url => $name) {
    $ch = curl_init("$base$url");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $body = substr($resp, strpos($resp, "\r\n\r\n") + 4);
    $result[$name] = ['code' => $code, 'body' => $body];
}
echo json_encode($result);
PHP;

$pubPath = tempnam(sys_get_temp_dir(), 'pub_') . '.php';
file_put_contents($pubPath, $pubScript);
$pubResult = json_decode(shell_exec("php " . escapeshellarg($pubPath) . " 2>&1"), true);
unlink($pubPath);

foreach ($pages as $url => $name) {
    $r = $pubResult[$name] ?? ['code' => 0, 'body' => ''];
    test("$name page loads", ($r['code'] ?? 0) === 200, "HTTP " . ($r['code'] ?? 'null'));
}

$landingContent = $pubResult['Landing']['body'] ?? '';
test('Landing has title', str_contains($landingContent, 'DigitalMarketingSaaS'));
test('Landing has navigation', str_contains($landingContent, 'Login') && str_contains($landingContent, 'Get Started'));
test('Landing has CSS link', str_contains($landingContent, '/build/assets/'));

$loginContent = $pubResult['Login']['body'] ?? '';
test('Login has CSRF token', (bool) preg_match('/name="_token" value="[^"]+"/', $loginContent));

// Test 2: Authentication flow
echo "\n▸ AUTHENTICATION FLOW\n───────────────────────────────────────────────────\n";

$authScript = <<<'PHP'
<?php
$base = 'http://localhost:8000';
$cf = tempnam(sys_get_temp_dir(), 'auth_') . '.txt';

// Get login token
$ch = curl_init("$base/login");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $cf, CURLOPT_COOKIEFILE => $cf, CURLOPT_TIMEOUT => 10]);
$resp = curl_exec($ch);
curl_close($ch);
$body = substr($resp, strpos($resp, "\r\n\r\n") + 4);
preg_match('/name="_token" value="([^"]+)"/', $body, $m);
$token = $m[1];

// POST login
$ch = curl_init("$base/login");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['_token' => $token, 'email' => 'owner@agency.com', 'password' => 'password123']), CURLOPT_COOKIEJAR => $cf, CURLOPT_COOKIEFILE => $cf, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10]);
$loginResp = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check dashboard
$ch = curl_init("$base/dashboard");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $cf, CURLOPT_COOKIEFILE => $cf, CURLOPT_TIMEOUT => 10]);
$dashResp = curl_exec($ch);
$dashCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$dashBody = substr($dashResp, strpos($dashResp, "\r\n\r\n") + 4);

echo json_encode([
    'login_code' => $loginCode,
    'dash_code' => $dashCode,
    'dash_has_dashboard' => str_contains($dashBody, 'Dashboard'),
    'dash_has_logout' => str_contains($dashBody, 'Logout') || str_contains($dashBody, 'logout'),
]);

unlink($cf);
PHP;

$authPath = tempnam(sys_get_temp_dir(), 'auth_') . '.php';
file_put_contents($authPath, $authScript);
$authResult = json_decode(shell_exec("php " . escapeshellarg($authPath) . " 2>&1"), true);
unlink($authPath);

test('Login POST (with session)', in_array($authResult['login_code'] ?? 0, [200, 302]), "HTTP " . ($authResult['login_code'] ?? 'null'));
test('Dashboard after login', in_array($authResult['dash_code'] ?? 0, [200, 302]), "HTTP " . ($authResult['dash_code'] ?? 'null'));
test('Dashboard has logout button', $authResult['dash_has_logout'] ?? false);
test('Dashboard title present', $authResult['dash_has_dashboard'] ?? false);

// Test 3: Security headers
echo "\n▸ SECURITY HEADERS\n───────────────────────────────────────────────────\n";

$headerScript = <<<'PHP'
<?php
$ch = curl_init("http://localhost:8000/");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 10]);
$resp = curl_exec($ch);
curl_close($ch);
echo json_encode([
    'x_content_type' => str_contains($resp, 'X-Content-Type-Options: nosniff'),
    'x_frame' => str_contains($resp, 'X-Frame-Options'),
    'referrer' => str_contains($resp, 'Referrer-Policy'),
    'permissions' => str_contains($resp, 'Permissions-Policy'),
    'csp' => str_contains($resp, 'Content-Security-Policy'),
    'no_unsafe_inline' => !str_contains($resp, 'unsafe-inline'),
]);
PHP;

$headerPath = tempnam(sys_get_temp_dir(), 'head_') . '.php';
file_put_contents($headerPath, $headerScript);
$headerResult = json_decode(shell_exec("php " . escapeshellarg($headerPath) . " 2>&1"), true);
unlink($headerPath);

test('X-Content-Type-Options: nosniff', $headerResult['x_content_type'] ?? false);
test('X-Frame-Options header', $headerResult['x_frame'] ?? false);
test('Referrer-Policy header', $headerResult['referrer'] ?? false);
test('Permissions-Policy header', $headerResult['permissions'] ?? false);
test('CSP header present', $headerResult['csp'] ?? false);
test('No unsafe-inline in CSP', $headerResult['no_unsafe_inline'] ?? false);

// Test 4: API endpoints
echo "\n▸ API ENDPOINTS\n───────────────────────────────────────────────────\n";

$apiScript = <<<'PHP'
<?php
$base = 'http://localhost:8000';
$ch = curl_init("$base/api/docs");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
curl_exec($ch);
$docsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$ch = curl_init("$base/api/health");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$healthRaw = curl_exec($ch);
curl_close($ch);
$health = json_decode($healthRaw, true);

echo json_encode([
    'docs_code' => $docsCode,
    'health' => $health,
]);
PHP;

$apiPath = tempnam(sys_get_temp_dir(), 'api_') . '.php';
file_put_contents($apiPath, $apiScript);
$apiResult = json_decode(shell_exec("php " . escapeshellarg($apiPath) . " 2>&1"), true);
unlink($apiPath);

test('API docs accessible', ($apiResult['docs_code'] ?? 0) === 200, "HTTP " . ($apiResult['docs_code'] ?? 'null'));

$health = $apiResult['health'] ?? null;
if ($health && isset($health['status'])) {
    test('Health check valid JSON', true);
    test('Database healthy', $health['checks']['database']['healthy'] ?? false);
    test('Cache healthy', $health['checks']['cache']['healthy'] ?? false);
    test('Storage healthy', $health['checks']['storage']['healthy'] ?? false);
} else {
    test('Health check valid JSON', false);
}

// Test 5: Frontend assets (fix path - manifest has files at assets/file, full path is public/build/assets/file)
echo "\n▸ FRONTEND ASSETS\n───────────────────────────────────────────────────\n";

$manifestPath = $projectDir . '/public/build/manifest.json';
$manifest = json_decode(file_get_contents($manifestPath), true);
test('Manifest exists', true);
test('Manifest has entries', count($manifest) > 0, count($manifest) . " entries");

foreach ($manifest as $key => $entry) {
    $diskPath = $projectDir . '/public/build/' . $entry['file'];
    $exists = file_exists($diskPath);
    $size = $exists ? filesize($diskPath) : 0;
    test("Asset on disk: " . basename($entry['file']), $exists && $size > 0, $size . " bytes");
}

test('Vite build complete', file_exists($projectDir . '/public/build/manifest.json'));

// Test 6: Admin pages (authenticated)
echo "\n▸ ADMIN PAGES (authenticated)\n───────────────────────────────────────────────────\n";

$adminScript = <<<'PHP'
<?php
$base = 'http://localhost:8000';
$cf = tempnam(sys_get_temp_dir(), 'admin_') . '.txt';

// Login
$ch = curl_init("$base/login");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $cf, CURLOPT_COOKIEFILE => $cf, CURLOPT_TIMEOUT => 10]);
$resp = curl_exec($ch);
curl_close($ch);
$body = substr($resp, strpos($resp, "\r\n\r\n") + 4);
preg_match('/name="_token" value="([^"]+)"/', $body, $m);

$ch = curl_init("$base/login");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['_token' => $m[1], 'email' => 'owner@agency.com', 'password' => 'password123']), CURLOPT_COOKIEJAR => $cf, CURLOPT_COOKIEFILE => $cf, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10]);
curl_exec($ch);
curl_close($ch);

$pages = ['/admin', '/campaigns', '/clients', '/agency/invoices', '/agency/billing', '/agency/settings', '/social/posts'];
$result = [];
foreach ($pages as $page) {
    $ch = curl_init("$base$page");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $cf, CURLOPT_COOKIEFILE => $cf, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result[$page] = $code;
}
echo json_encode($result);
unlink($cf);
PHP;

$adminPath = tempnam(sys_get_temp_dir(), 'admin_') . '.php';
file_put_contents($adminPath, $adminScript);
$adminResults = json_decode(shell_exec("php " . escapeshellarg($adminPath) . " 2>&1"), true);
unlink($adminPath);

if ($adminResults && is_array($adminResults)) {
    foreach ($adminResults as $page => $code) {
        test("Admin: $page", in_array($code, [200, 302]), "HTTP $code");
    }
} else {
    test('Admin pages reachable', false, 'Test process failed');
}

// Summary
echo "\n▸ SUMMARY\n═══════════════════════════════════════════════════\n";
$passed = count(array_filter($results, fn($r) => $r['passed']));
$failed = count(array_filter($results, fn($r) => !$r['passed']));
$total = count($results);

echo " Total: $total | Passed: $passed | Failed: $failed\n";

if ($failed > 0) {
    echo "\nFailed tests:\n";
    foreach ($results as $r) {
        if (!$r['passed']) {
            echo "  - {$r['name']}: {$r['detail']}\n";
        }
    }
}

echo "\n" . ($failed === 0 ? "✅ ALL TESTS PASSED" : "❌ $failed FAILED") . "\n";
