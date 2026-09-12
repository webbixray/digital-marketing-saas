<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use existing X-Request-ID from client if provided, otherwise generate one
        $requestId = $request->header('X-Request-ID') ?: Str::uuid()->toString();

        // Store in request for potential use in controllers/logs
        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);

        // Add X-Request-ID to all responses
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
