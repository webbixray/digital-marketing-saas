<?php

namespace App\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait HandlesErrors
{
    /**
     * Execute a callback with consistent error handling for API requests.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  array<string, mixed>  $logContext
     * @return T|JsonResponse
     */
    protected function handleApiOperation(
        callable $callback,
        Request $request,
        string $errorMessage = 'An error occurred.',
        int $errorCode = 500,
        array $logContext = [],
    ) {
        try {
            return $callback();
        } catch (ValidationException $e) {
            Log::warning($errorMessage, array_merge($logContext, [
                'type' => 'validation',
                'errors' => $e->errors(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthenticationException $e) {
            Log::warning($errorMessage, array_merge($logContext, [
                'type' => 'authentication',
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        } catch (ModelNotFoundException|NotFoundHttpException $e) {
            Log::info($errorMessage, array_merge($logContext, [
                'type' => 'not_found',
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        } catch (Throwable $e) {
            Log::error($errorMessage, array_merge($logContext, [
                'type' => 'exception',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            $message = config('app.debug') ? $e->getMessage() : $errorMessage;

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $errorCode);
        }
    }

    /**
     * Execute a callback with consistent error handling for web requests.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  array<string, mixed>  $logContext
     * @return T|RedirectResponse
     */
    protected function handleWebOperation(
        callable $callback,
        string $errorMessage = 'An error occurred.',
        string $redirectRoute = 'dashboard',
        array $logContext = [],
    ) {
        try {
            return $callback();
        } catch (ValidationException $e) {
            Log::warning($errorMessage, array_merge($logContext, [
                'type' => 'validation',
                'errors' => $e->errors(),
            ]));

            throw $e; // Let Laravel handle validation errors natively
        } catch (ModelNotFoundException $e) {
            Log::info($errorMessage, array_merge($logContext, [
                'type' => 'not_found',
                'model' => $e->getModel(),
            ]));

            abort(404);
        } catch (Throwable $e) {
            Log::error($errorMessage, array_merge($logContext, [
                'type' => 'exception',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return redirect()->route($redirectRoute)->with('error', $errorMessage);
        }
    }
}
