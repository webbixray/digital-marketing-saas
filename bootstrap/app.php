<?php

use App\Http\Middleware\AgentRateLimit;
use App\Http\Middleware\CacheWithEtag;
use App\Http\Middleware\Enforce2FA;
use App\Http\Middleware\EnforceAiCredits;
use App\Http\Middleware\EnforcePlatformRateLimit;
use App\Http\Middleware\EnforceQuota;
use App\Http\Middleware\EnsureAgencyAccess;
use App\Http\Middleware\HstsMiddleware;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleApiRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
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
            'quota' => EnforceQuota::class,
            'ai.credits' => EnforceAiCredits::class,
            '2fa' => Enforce2FA::class,
            'platform.rate_limit' => EnforcePlatformRateLimit::class,
            'agent.rate_limit' => AgentRateLimit::class,
            'throttle.api' => ThrottleApiRequests::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
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

        $middleware->api(prepend: [
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            NotFoundHttpException::class,
            AccessDeniedHttpException::class,
            MethodNotAllowedHttpException::class,
        ]);

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'status' => 404,
                ], 404);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                    'status' => 403,
                ], 403);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'status' => 401,
                ], 401);
            }
        });

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
        $exceptions->reportable(function (Throwable $e) {
            if (! app()->bound('sentry')) {
                return;
            }
            app('sentry')->captureException($e);
        });
    })
    ->create();
