<?php

use App\Http\Middleware\AgentRateLimit;
use App\Http\Middleware\CacheWithEtag;
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
            'cache.etag' => CacheWithEtag::class,
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

        // Trim whitespace and empty strings from input
        $middleware->api(prepend: [
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
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

        // AuthenticationException -> 401 JSON for API
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'status' => 401,
                ], 401);
            }
        });

        // ValidationException -> 422 JSON with errors
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                    'status' => 422,
                ], 422);
            }
        });

        // Generic HTTP exception -> JSON for API
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'status' => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry reporting
        $exceptions->reportable(function (\Throwable $e) {
            if (! app()->bound('sentry')) {
                return;
            }
            app('sentry')->captureException($e);
        });
    })
    ->create();
