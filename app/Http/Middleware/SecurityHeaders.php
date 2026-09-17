<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
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

        // Content Security Policy for HTML responses
        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $nonce = base64_encode(random_bytes(16));
            $csp = "default-src 'self'; ";
            $csp .= "script-src 'self' 'unsafe-inline' https://cdn.adminlte.io https://cdn.jsdelivr.net https://code.jquery.com; ";
            $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.adminlte.io; ";
            $csp .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.adminlte.io; ";
            $csp .= "img-src 'self' data: https://cdn.adminlte.io https://cdnjs.cloudflare.com https://unpkg.com https://ui-avatars.com blob:; ";
            $csp .= "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ";
            $csp .= "frame-ancestors 'self'; ";
            $csp .= "base-uri 'self'; ";
            $csp .= "form-action 'self'; ";
            $csp .= "object-src 'none'; ";
            $csp .= 'upgrade-insecure-requests;';
            $response->headers->set('Content-Security-Policy', $csp);
            // Share nonce with views for script/style tags
            view()->share('cspNonce', $nonce);
        }

        return $response;
    }
}
