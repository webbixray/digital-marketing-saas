<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Standardize exception responses for API routes.
 * Converts abort() calls and HttpExceptions to proper JSON responses
 * when the request is JSON or matches api/* patterns.
 */
class StandardizeJsonExceptions
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
            return $response;
        } catch (\Throwable $e) {
            if ($this->isApiRequest($request)) {
                return $this->toJsonResponse($e);
            }
            throw $e;
        }
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->wantsJson()
            || $request->is('api/*')
            || $request->ajax();
    }

    private function toJsonResponse(\Throwable $e): JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage() ?: $this->defaultMessage($statusCode);

            Log::warning("API exception standardized", [
                'status' => $statusCode,
                'message' => $message,
            ]);

            return response()->json([
                'error' => $message,
                'status_code' => $statusCode,
            ], $statusCode);
        }

        Log::error("API unhandled exception", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);

        $statusCode = $e instanceof \Exception ? 500 : 400;
        return response()->json([
            'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            'status_code' => $statusCode,
        ], $statusCode);
    }

    private function defaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            403 => 'Forbidden. You do not have permission to perform this action.',
            404 => 'Resource not found.',
            422 => 'Validation failed.',
            429 => 'Too many requests.',
            500 => 'Internal server error.',
            503 => 'Service unavailable.',
            default => 'An error occurred.',
        };
    }
}
