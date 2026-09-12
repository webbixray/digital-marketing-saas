<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AgentCostLog;
use App\Models\AiContentLog;
use App\Models\LandingPage;
use App\Models\SocialPost;
use App\Services\AI\Agent\AgentHealthMonitor;
use App\Services\Analytics\AnalyticsService;
use App\Services\QuotaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private readonly QuotaService $quota,
        private readonly AnalyticsService $analytics,
        private readonly AgentHealthMonitor $healthMonitor,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;

        // Use cached analytics service
        $stats = $this->analytics->getOverviewStats($agency);

        // Quotas
        $quotas = [
            'posts' => [
                'label' => 'Social Posts',
                'used' => $stats['total_posts'],
                'limit' => $this->quota->getLimit($agency, 'posts'),
                'percentage' => $this->quota->getPercentage($agency, 'posts', $stats['total_posts']),
            ],
            'ai' => [
                'label' => 'AI Generations',
                'used' => Cache::remember("analytics:{$agency->id}:ai_generations", 300, function () use ($agency) {
                    return AiContentLog::where('agency_id', $agency->id)->count();
                }),
                'limit' => $this->quota->getLimit($agency, 'ai_generations'),
                'percentage' => $this->quota->getPercentage($agency, 'ai_generations', Cache::remember("analytics:{$agency->id}:ai_generations", 300, function () use ($agency) {
                    return AiContentLog::where('agency_id', $agency->id)->count();
                })),
            ],
            'campaigns' => [
                'label' => 'Campaigns',
                'used' => $stats['total_campaigns'],
                'limit' => $this->quota->getLimit($agency, 'campaigns'),
                'percentage' => $this->quota->getPercentage($agency, 'campaigns', $stats['total_campaigns']),
            ],
            'clients' => [
                'label' => 'Clients',
                'used' => $stats['total_clients'],
                'limit' => $this->quota->getLimit($agency, 'clients'),
                'percentage' => $this->quota->getPercentage($agency, 'clients', $stats['total_clients']),
            ],
            'users' => [
                'label' => 'Team Members',
                'used' => $agency->users_count ?? $agency->users()->count(),
                'limit' => $this->quota->getLimit($agency, 'users'),
                'percentage' => $this->quota->getPercentage($agency, 'users', $agency->users_count ?? $agency->users()->count()),
            ],
            'accounts' => [
                'label' => 'Social Accounts',
                'used' => $stats['active_social_accounts'],
                'limit' => $this->quota->getLimit($agency, 'social_accounts'),
                'percentage' => $this->quota->getPercentage($agency, 'social_accounts', $stats['active_social_accounts']),
            ],
            'invoices' => [
                'label' => 'Invoices',
                'used' => $stats['pending_invoices'],
                'limit' => $this->quota->getLimit($agency, 'invoices'),
                'percentage' => $this->quota->getPercentage($agency, 'invoices', $stats['pending_invoices']),
            ],
            'landing_pages' => [
                'label' => 'Landing Pages',
                'used' => Cache::remember("analytics:{$agency->id}:landing_pages", 300, function () use ($agency) {
                    return LandingPage::where('agency_id', $agency->id)->count();
                }),
                'limit' => $this->quota->getLimit($agency, 'landing_pages'),
                'percentage' => $this->quota->getPercentage($agency, 'landing_pages', Cache::get("analytics:{$agency->id}:landing_pages", 0)),
            ],
        ];

        // Recent activity
        $recentActivity = ActivityLog::where('agency_id', $agency->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Upcoming scheduled posts
        $upcomingPosts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->with('socialAccount')
            ->orderBy('scheduled_at', 'asc')
            ->take(5)
            ->get();

        // Agent health summary
        $agentHealthSummary = $this->getAgentHealthSummary($agency->id);

        // Recent agent activity
        $recentAgentActivity = $this->getRecentAgentActivity($agency->id);

        return view('dashboard.index', compact('stats', 'quotas', 'recentActivity', 'upcomingPosts', 'agency', 'agentHealthSummary', 'recentAgentActivity'));
    }

    /**
     * Get agent health summary for dashboard widget.
     */
    private function getAgentHealthSummary(int $agencyId): array
    {
        try {
            $systemHealth = $this->healthMonitor->getSystemHealth();

            return [
                'system_score' => $systemHealth['system_score'] ?? 100,
                'overall_status' => $systemHealth['overall_status'] ?? 'healthy',
                'total_agents' => $systemHealth['total_agents'] ?? 0,
                'healthy_agents' => $systemHealth['healthy_agents'] ?? 0,
                'degraded_agents' => $systemHealth['degraded_agents'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'system_score' => 100,
                'overall_status' => 'healthy',
                'total_agents' => 0,
                'healthy_agents' => 0,
                'degraded_agents' => 0,
            ];
        }
    }

    /**
     * Get recent agent activity for dashboard widget.
     */
    private function getRecentAgentActivity(int $agencyId): Collection
    {
        try {
            return AgentCostLog::byAgency($agencyId)
                ->orderBy('executed_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }
}
