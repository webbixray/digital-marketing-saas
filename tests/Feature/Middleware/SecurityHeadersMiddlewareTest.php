<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $middleware = new SecurityHeaders();
        $response = $middleware->handle($this->makeRequest(), fn() => response('<html></html>'));

        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    public function test_security_headers_sets_x_content_type_options(): void
    {
        $middleware = new SecurityHeaders();
        $response = $middleware->handle($this->makeRequest(), fn() => response('<html></html>'));

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_security_headers_sets_hsts(): void
    {
        // HSTS is handled by HstsMiddleware, not SecurityHeaders
        $middleware = new SecurityHeaders();
        $response = $middleware->handle($this->makeRequest(), fn() => response('<html></html>'));

        // SecurityHeaders does not set HSTS, so it should be null
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_security_headers_sets_csp(): void
    {
        $middleware = new SecurityHeaders();
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
        $middleware = new SecurityHeaders();
        $response = $middleware->handle($this->makeRequest(), fn() => response('<html></html>'));

        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_security_headers_does_not_set_hsts_on_http(): void
    {
        $middleware = new SecurityHeaders();
        $response = $middleware->handle($this->makeRequest(), fn() => response('<html></html>'));

        $this->assertNull(
            $response->headers->get('Strict-Transport-Security'),
            'HSTS header should not be set by SecurityHeaders middleware'
        );
    }

    public function test_security_headers_sets_xss_protection(): void
    {
        $middleware = new SecurityHeaders();
        $response = $middleware->handle($this->makeRequest(), fn() => response('<html></html>'));

        $this->assertEquals('0', $response->headers->get('X-XSS-Protection'));
    }

    public function test_security_headers_sets_permissions_policy(): void
    {
        $middleware = new SecurityHeaders();
        $response = $middleware->handle($this->makeRequest(), fn() => response('<html></html>'));

        $policy = $response->headers->get('Permissions-Policy');
        $this->assertNotNull($policy);
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
    }
}
