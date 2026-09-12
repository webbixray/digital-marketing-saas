<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry\State\Scope;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // Report to Sentry if configured
        $this->reportable(function (Throwable $e) {
            if (!$this->shouldReport($e) || !app()->bound('sentry')) {
                return;
            }
            app('sentry')->configureScope(function (Scope $scope) {
                $scope->setContext('request', [
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'user_id' => auth()->id(),
                    'agency_id' => auth()->user()?->agency_id,
                ]);
            });
            app('sentry')->captureException($e);
        });

        // JSON response for API auth failures
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'authentication_required',
                ], 401);
            }
        });

        // JSON response for authorization failures
        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Forbidden.',
                    'error' => 'forbidden',
                ], 403);
            }
        });

        // JSON response for model not found
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'message' => "{$model} not found.",
                    'error' => 'not_found',
                ], 404);
            }
        });

        // JSON response for validation failures
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                    'error' => 'validation_error',
                ], 422);
            }
        });

        // JSON response for 404
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => 'not_found',
                ], 404);
            }
        });

        // JSON response for 405
        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Method not allowed.',
                    'error' => 'method_not_allowed',
                ], 405);
            }
        });

        // JSON response for 429 (rate limit)
        $this->renderable(function (TooManyRequestsHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Too many requests.',
                    'error' => 'rate_limit_exceeded',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                ], 429);
            }
        });
    }

    public function report(Throwable $e): void
    {
        if ($this->shouldReport($e) && $e instanceof \Exception) {
            Log::channel('errors')->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace_id' => request()->header('X-Request-ID', Str::uuid()->toString()),
                'user_id' => auth()->id(),
                'agency_id' => auth()->user()?->agency_id,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
            ]);
        }

        parent::report($e);
    }

    protected function shouldReturnJson(Throwable $e, $request): bool
    {
        return $request->is('api/*') || $request->wantsJson() || parent::shouldReturnJson($e, $request);
    }
}
