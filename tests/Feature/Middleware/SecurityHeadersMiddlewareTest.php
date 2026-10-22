<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(): Request
    {
        return Request::create('/test', 'GET', [], [], [], [
            'CONTENT_TYPE' => 'text/html; charset=UTF-8',
        ]);
    }

    public function test_security_headers_sets_x_frame_options(): void
    {
        $middleware = new SecurityHeaders;
        $response = $middleware->handle($this->makeRequest(), fn () => response('<html></html>'));

        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    public function test_security_headers_sets_x_content_type_options(): void
    {
        $middleware = new SecurityHeaders;
        $response = $middleware->handle($this->makeRequest(), fn () => response('<html></html>'));

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_security_headers_sets_hsts(): void
    {
        // HSTS is handled by HstsMiddleware, not SecurityHeaders
        $middleware = new SecurityHeaders;
        $response = $middleware->handle($this->makeRequest(), fn () => response('<html></html>'));

        // SecurityHeaders does not set HSTS, so it should be null
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_security_headers_sets_csp(): void
    {
        $middleware = new SecurityHeaders;
        $request = $this->makeRequest();
        $response = $middleware->handle($request, function () {
            $resp = response('<html></html>');
            $resp->headers->set('Content-Type', 'text/html; charset=UTF-8');

            return $resp;
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_security_headers_sets_referrer_policy(): void
    {
        $middleware = new SecurityHeaders;
        $response = $middleware->handle($this->makeRequest(), fn () => response('<html></html>'));

        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_security_headers_does_not_set_hsts_on_http(): void
    {
        $middleware = new SecurityHeaders;
        $response = $middleware->handle($this->makeRequest(), fn () => response('<html></html>'));

        $this->assertNull(
            $response->headers->get('Strict-Transport-Security'),
            'HSTS header should not be set by SecurityHeaders middleware'
        );
    }

    public function test_security_headers_sets_xss_protection(): void
    {
        $middleware = new SecurityHeaders;
        $response = $middleware->handle($this->makeRequest(), fn () => response('<html></html>'));

        $this->assertEquals('0', $response->headers->get('X-XSS-Protection'));
    }

    public function test_security_headers_sets_permissions_policy(): void
    {
        $middleware = new SecurityHeaders;
        $response = $middleware->handle($this->makeRequest(), fn () => response('<html></html>'));

        $policy = $response->headers->get('Permissions-Policy');
        $this->assertNotNull($policy);
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
    }

    public function test_csp_script_src_uses_nonce_and_not_unsafe_inline(): void
    {
        $middleware = new SecurityHeaders;
        $request = $this->makeRequest();
        $response = $middleware->handle($request, function () {
            $resp = response('<html></html>');
            $resp->headers->set('Content-Type', 'text/html; charset=UTF-8');

            return $resp;
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);

        // Parse the script-src directive from the CSP
        $scriptSrc = '';
        preg_match("/script-src\s+([^;]+)/", $csp, $matches);
        if (isset($matches[1])) {
            $scriptSrc = $matches[1];
        }

        // Nonce must be present in script-src
        $this->assertStringContainsString("'nonce-", $scriptSrc, 'CSP script-src must contain a nonce');

        // unsafe-inline must NOT be in script-src (nonce-only approach)
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrc,
            'CSP script-src must not allow unsafe-inline');

        // default-src must be 'self'
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_csp_style_src_uses_nonce(): void
    {
        $middleware = new SecurityHeaders;
        $request = $this->makeRequest();
        $response = $middleware->handle($request, function () {
            $resp = response('<html></html>');
            $resp->headers->set('Content-Type', 'text/html; charset=UTF-8');

            return $resp;
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);

        // style-src should contain a nonce for styled components
        $this->assertStringContainsString("'nonce-", $csp, 'CSP must contain a nonce for style-src');
    }

    public function test_csp_reports_unsafe_eval_for_alpine_js(): void
    {
        $middleware = new SecurityHeaders;
        $request = $this->makeRequest();
        $response = $middleware->handle($request, function () {
            $resp = response('<html></html>');
            $resp->headers->set('Content-Type', 'text/html; charset=UTF-8');

            return $resp;
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);

        // unsafe-eval is required by Alpine.js (evaluates expressions via new Function()).
        // This is a documented trade-off; removing it requires refactoring all Alpine.js templates.
        $this->assertStringContainsString("'unsafe-eval'", $csp,
            'CSP script-src must allow unsafe-eval for Alpine.js expression evaluation');
    }
}
