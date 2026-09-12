<?php

use App\Http\Middleware\AgentRateLimit;
use App\Http\Middleware\Enforce2FA;
use App\Http\Middleware\EnforcePlatformRateLimit;
use App\Http\Middleware\EnforceQuota;
use App\Http\Middleware\EnsureAgencyAccess;
use App\Http\Middleware\FeatureGate;
use App\Http\Middleware\HstsMiddleware;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'agency' => EnsureAgencyAccess::class,
            'feature' => FeatureGate::class,
            'quota' => EnforceQuota::class,
            '2fa' => Enforce2FA::class,
            'platform.rate_limit' => EnforcePlatformRateLimit::class,
            'agent.rate_limit' => AgentRateLimit::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
            AddLinkHeadersForPreloadedAssets::class,
            HstsMiddleware::class,
            RequestId::class,
        ]);

        $middleware->api(append: [
            SubstituteBindings::class,
            RequestId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            NotFoundHttpException::class,
            AccessDeniedHttpException::class,
            MethodNotAllowedHttpException::class,
        ]);

        // ModelNotFoundException -> 404 JSON for API
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'status' => 404,
                ], 404);
            }
        });

        // AuthorizationException -> 403 JSON for API
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                    'status' => 403,
                ], 403);
            }
        });

        // ValidationException -> 422 JSON for API
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error.',
                    'status' => 422,
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // AuthenticationException -> 401 JSON for API
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                    'status' => 401,
                ], 401);
            }
        });

        // Fallback for all API 404/500 errors
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = match (true) {
                    $e instanceof HttpException => $e->getStatusCode(),
                    default => 500,
                };

                $message = config('app.debug') ? $e->getMessage() : match ($statusCode) {
                    400 => 'Bad request.',
                    404 => 'Resource not found.',
                    405 => 'Method not allowed.',
                    429 => 'Rate limit exceeded. Please try again later.',
                    500 => 'An unexpected error occurred. Please try again later.',
                    default => 'An error occurred.',
                };

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'status' => $statusCode,
                ], $statusCode);
            }
        });

        // Log all non-HTTP exceptions for monitoring
        $exceptions->report(function (Throwable $e) {
            if (! $e instanceof HttpException && ! $e instanceof ValidationException) {
                Log::critical('Unhandled exception', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
    })->create();
