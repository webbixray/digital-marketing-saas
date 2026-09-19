<?php

namespace App\Http\Middleware;

use App\Services\QuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceQuota
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized.'], 401);
            }
            abort(403);
        }

        $agency = $user->agency;
        if (! $agency) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Agency not found.'], 404);
            }
            abort(404);
        }

        $quotaService = app(QuotaService::class);

        if ($quotaService->isOverQuota($agency, $feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => "You have reached your {$feature} quota limit.",
                    'upgrade_url' => route('agency.settings'),
                    'quota_status' => $quotaService->getQuotaStatus($agency),
                ], 429);
            }
            abort(429, "You have reached your {$feature} quota limit. Please upgrade your plan.");
        }

        return $next($request);
    }
}
