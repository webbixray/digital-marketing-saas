<?php

namespace App\Http\Controllers;

use App\Jobs\Email\SendEmailCampaign;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

trait HandlesErrors
{
    /**
     * Handle an action with centralized error handling.
     *
     * @param callable $action The action to execute
     * @param string $errorMessage Generic error message for unexpected errors
     * @param array $successRedirect ['route' => ..., 'params' => [...], 'message' => ...]
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function handleAction(callable $action, string $errorMessage = 'An error occurred', array $successRedirect = []): mixed
    {
        try {
            $result = $action();

            // Handle success redirect for web routes
            if (! empty($successRedirect) && !$this->expectsJson()) {
                return redirect()->route(
                    $successRedirect['route'],
                    $successRedirect['params'] ?? []
                )->with('success', $successRedirect['message'] ?? 'Operation successful');
            }

            return $result;
        } catch (ValidationException $e) {
            throw $e; // Let Laravel handle validation errors
        } catch (AuthorizationException $e) {
            throw $e; // Let Laravel handle authorization errors
        } catch (ModelNotFoundException $e) {
            abort(404);
        } catch (HttpException $e) {
            throw $e; // Let Laravel handle HTTP exceptions
        } catch (\InvalidArgumentException $e) {
            if ($this->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e); // Log the error

            if ($this->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => app()->isProduction() ? $errorMessage : $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withErrors(['error' => $errorMessage]);
        }
    }

    /**
     * Handle an API action with centralized error handling.
     *
     * @param callable $action
     * @param string $errorMessage
     * @return mixed
     */
    protected function handleApiAction(callable $action, string $errorMessage = 'An error occurred'): mixed
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => app()->isProduction() ? $errorMessage : $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if the request expects JSON.
     */
    protected function expectsJson(): bool
    {
        return request()->expectsJson() || request()->is('api/*') || request()->wantsJson();
    }
}
