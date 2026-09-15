<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiContentLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        return response()->json([
            'overview' => Cache::remember("api:dashboard:{$agencyId}:overview", 300, function () use ($agencyId) {
                return [
                    'total_clients' => Client::where('agency_id', $agencyId)->count(),
                    'total_posts' => SocialPost::forAgency($agencyId)->count(),
                    'total_campaigns' => Campaign::where('agency_id', $agencyId)->count(),
                    'total_revenue' => Invoice::where('agency_id', $agencyId)->paid()->sum('total'),
                    'pending_invoices' => Invoice::where('agency_id', $agencyId)->pending()->count(),
                    'active_social_accounts' => SocialAccount::where('agency_id', $agencyId)->count(),
                ];
            }),
            'social' => Cache::remember("api:dashboard:{$agencyId}:social", 300, function () use ($agencyId) {
                return [
                    'total_posts' => SocialPost::forAgency($agencyId)->count(),
                    'published' => SocialPost::forAgency($agencyId)->published()->count(),
                    'scheduled' => SocialPost::forAgency($agencyId)->scheduled()->count(),
                    'failed' => SocialPost::forAgency($agencyId)->where('status', 'failed')->count(),
                ];
            }),
            'ai' => Cache::remember("api:dashboard:{$agencyId}:ai", 300, function () use ($agencyId) {
                return [
                    'total_generations' => AiContentLog::where('agency_id', $agencyId)->count(),
                    'successful' => AiContentLog::where('agency_id', $agencyId)->where('status', 'success')->count(),
                    'total_cost' => AiContentLog::where('agency_id', $agencyId)->sum('cost_usd'),
                ];
            }),
        ]);
    }
}
