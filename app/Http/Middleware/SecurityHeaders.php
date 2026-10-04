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
        // Generate + share the nonce BEFORE the response renders so that views
        // emit the same nonce that this middleware later puts in the CSP header.
        $nonce = $request->attributes->get('csp_nonce');
        if ($nonce === null) {
            $nonce = base64_encode(random_bytes(16));
            $request->attributes->set('csp_nonce', $nonce);
        }
        View::share('cspNonce', $nonce);

        // Propagate the nonce to the Vite plugin so package-injected scripts
        // (e.g. Laravel Boost's browser-logs bridge) are also CSP-compliant.
        app(Vite::class)->useCspNonce($nonce);

        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking - only allow same origin framing
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // XSS Protection for older browsers
        $response->headers->set('X-XSS-Protection', '0');

        // Referrer Policy - don't leak URL to third parties
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy - restrict browser features
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // Content Security Policy for HTML responses (nonce-based)
        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $csp = "default-src 'self'; ";
            // 'unsafe-eval' required: Alpine.js evaluates expressions via new Function().
            // Nonce covers app inline scripts; strict-dynamic is not viable without refactoring all views.
            $csp .= "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://cdn.adminlte.io https://cdn.jsdelivr.net https://code.jquery.com https://cdn.tailwindcss.com; ";
            $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.adminlte.io https://cdn.jsdelivr.net; ";
            $csp .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.adminlte.io; ";
            $csp .= "img-src 'self' data: https://cdn.adminlte.io https://cdnjs.cloudflare.com https://unpkg.com https://ui-avatars.com blob:; ";
            $csp .= "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ";
            $csp .= "frame-ancestors 'self'; ";
            $csp .= "base-uri 'self'; ";
            $csp .= "form-action 'self'; ";
            $csp .= "object-src 'none'; ";
            $csp .= 'upgrade-insecure-requests;';
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
