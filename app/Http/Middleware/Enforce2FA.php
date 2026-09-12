<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Enforce2FA
{
    /**
     * Handle an incoming request.
     * Requires 2FA to be enabled for the user if agency has it enforced.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check if agency enforces 2FA
        $agency = $user->agency;
        if ($agency && ($agency->settings['enforce_2fa'] ?? false)) {
            if (! $user->two_factor_enabled) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Two-factor authentication is required. Please enable it in your settings.',
                    ], 403);
                }

                return redirect()->route('auth.two-factor')
                    ->with('warning', 'Two-factor authentication is required for your agency.');
            }
        }

        return $next($request);
    }
}
