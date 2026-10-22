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

        // 2FA is mandatory for owner/admin roles regardless of agency setting.
        // Agency-level enforce_2fa extends this to editors/members as well.
        $isOwnerOrAdmin = in_array($user->role, ['owner', 'admin'], true);
        $agencyEnforces2fa = $user->agency
            && ($user->agency->custom_settings['enforce_2fa'] ?? false);

        if ($isOwnerOrAdmin || $agencyEnforces2fa) {
            if (! $user->two_factor_enabled) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Two-factor authentication is required for your account. Please enable it in your settings.',
                    ], 403);
                }

                return redirect()->route('two-factor.show')
                    ->with('warning', 'Two-factor authentication is required for your account.');
            }
        }

        return $next($request);
    }
}
