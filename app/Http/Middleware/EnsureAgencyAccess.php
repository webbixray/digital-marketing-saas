<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized.'], 401);
            }

            return redirect()->route('login');
        }

        $agencyId = $user->agency_id;

        if (! $agencyId) {
            $raw = DB::table('users')->where('id', $user->id)->value('agency_id');
            $agencyId = $raw;
        }

        if (! $agencyId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No agency assigned.'], 403);
            }
            abort(403, 'No agency assigned to your account.');
        }

        $request->attributes->set('agency_id', $agencyId);
        view()->share('currentAgency', ['id' => $agencyId, 'status' => 'active']);

        return $next($request);
    }
}
