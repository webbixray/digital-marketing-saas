<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheWithEtag
{
    public function handle(Request $request, Closure $next, int $ttl = 300): Response
    {
        $response = $next($request);

        if (!$response->isSuccessful() || $request->getMethod() !== 'GET') {
            return $response;
        }

        $content = $response->getContent();
        $etag = '"' . md5($content) . '"';

        $response->setEtag($etag);
        $response->setPublic();
        $response->setMaxAge($ttl);

        if ($response->isNotModified($request)) {
            return response()->noContent(304);
        }

        return $response;
    }
}
