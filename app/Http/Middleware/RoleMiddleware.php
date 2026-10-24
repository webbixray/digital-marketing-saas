<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Use the raw column value: Spatie's HasRoles trait overrides the
        // "role" attribute accessor to return the first Spatie role name,
        // which is NULL for users whose agency role lives in the column only
        // (all agency members registered before Spatie role sync).
        $userRole = $user->getOriginal('role');

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Unauthorized role.');
        }

        return $next($request);
    }
}