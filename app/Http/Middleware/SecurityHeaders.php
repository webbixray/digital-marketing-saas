<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get CSP nonce from request (set by CspNonce middleware)
        $nonce = $request->attributes->get('csp_nonce');
        if ($nonce === null) {
            // Fallback: generate if CspNonce middleware didn't run
            $nonce = base64_encode(random_bytes(32));
            $request->attributes->set('csp_nonce', $nonce);
        }
        View::share('cspNonce', $nonce);

        // Propagate the nonce to the Vite plugin so package-injected scripts
        // (e.g. Laravel Boost's browser-logs bridge) are also CSP-compliant.
        app(Vite::class)->useCspNonce($nonce);

        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking - deny framing
        $response->headers->set('X-Frame-Options', 'DENY');

        // XSS Protection for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy - don't leak URL to third parties
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy - restrict browser features
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        );

        // Content Security Policy for HTML responses (nonce-based, no unsafe-inline/eval)
        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $csp = "default-src 'self'; ";
            // Remove 'unsafe-eval' - requires refactoring Alpine.js expressions
            // Remove 'unsafe-inline' - use nonces for all inline scripts/styles
            $csp .= "script-src 'self' 'nonce-{$nonce}' https://cdn.adminlte.io https://cdn.jsdelivr.net https://code.jquery.com https://cdn.tailwindcss.com; ";
            $csp .= "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.adminlte.io https://cdn.jsdelivr.net; ";
            $csp .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.adminlte.io; ";
            $csp .= "img-src 'self' data: https://cdn.adminlte.io https://cdnjs.cloudflare.com https://unpkg.com https://ui-avatars.com blob:; ";
            $csp .= "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ";
            $csp .= "frame-ancestors 'none'; ";
            $csp .= "base-uri 'self'; ";
            $csp .= "form-action 'self'; ";
            $csp .= "object-src 'none'; ";
            $csp .= 'upgrade-insecure-requests;';
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
