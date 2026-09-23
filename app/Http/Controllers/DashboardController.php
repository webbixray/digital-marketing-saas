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

        // Get all stats - cached at service level
        $overviewStats = $this->analytics->getOverviewStats($agency);
        $socialStats = $this->analytics->getSocialStats($agency);

        // Combine stats for the modern dashboard view
        $stats = [
            'total_posts' => $overviewStats['total_posts'],
            'published_posts' => $socialStats['published'],
            'pending_posts' => $socialStats['scheduled'],
            'failed_posts' => $socialStats['failed'],
            'draft_posts' => $socialStats['draft'],
            'total_engagement' => $socialStats['total_engagement'],
            'average_engagement' => $socialStats['average_engagement'],
            'total_clients' => $overviewStats['total_clients'],
            'total_campaigns' => $overviewStats['total_campaigns'],
            'total_revenue' => $overviewStats['total_revenue'],
            'pending_invoices' => $overviewStats['pending_invoices'],
            'active_social_accounts' => $overviewStats['active_social_accounts'],
        ];

        // Platform stats for the performance section
        $platformStats = $socialStats['by_platform'] ?? [];

        // Quotas
        $userCount = $agency->users_count ?? $agency->users()->count();

        $quotas = [
            'posts' => [
                'label' => 'Social Posts',
                'used' => $stats['total_posts'],
                'limit' => $this->quota->getLimit($agency, 'posts'),
                'percentage' => $this->quota->getPercentage($agency, 'posts', $stats['total_posts']),
            ],
            'ai' => (function () use ($agency) {
                $aiCount = Cache::remember("analytics:{$agency->id}:ai_generations", 300, function () use ($agency) {
                    return AiContentLog::where('agency_id', $agency->id)->count();
                });
                return [
                    'label' => 'AI Generations',
                    'used' => $aiCount,
                    'limit' => $this->quota->getLimit($agency, 'ai_generations'),
                    'percentage' => $this->quota->getPercentage($agency, 'ai_generations', $aiCount),
                ];
            })(),
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
                'used' => $userCount,
                'limit' => $this->quota->getLimit($agency, 'users'),
                'percentage' => $this->quota->getPercentage($agency, 'users', $userCount),
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
            'landing_pages' => (function () use ($agency) {
                $lpCount = Cache::remember("analytics:{$agency->id}:landing_pages", 300, function () use ($agency) {
                    return LandingPage::where('agency_id', $agency->id)->count();
                });
                return [
                    'label' => 'Landing Pages',
                    'used' => $lpCount,
                    'limit' => $this->quota->getLimit($agency, 'landing_pages'),
                    'percentage' => $this->quota->getPercentage($agency, 'landing_pages', $lpCount),
                ];
            })(),
        ];

        // Recent activity - eager loaded user relationship
        $recentActivity = Cache::remember("dashboard:{$agency->id}:recent_activity", 60, function () use ($agency) {
            return ActivityLog::where('agency_id', $agency->id)
                ->with(['user:id,name,email,avatar'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        });

        // Upcoming scheduled posts - eager loaded with socialAccount
        $upcomingPosts = Cache::remember("dashboard:{$agency->id}:upcoming_posts", 60, function () use ($agency) {
            return SocialPost::where('agency_id', $agency->id)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>', now())
                ->with(['socialAccount:id,platform,platform_username,platform_display_name'])
                ->orderBy('scheduled_at', 'asc')
                ->take(5)
                ->get();
        });

        // Agent health summary
        $agentHealthSummary = $this->getAgentHealthSummary($agency->id);

        // Recent agent activity - cached
        $recentAgentActivity = Cache::remember("dashboard:{$agency->id}:agent_activity", 120, function () use ($agency) {
            return AgentCostLog::where('agency_id', $agency->id)
                ->orderBy('executed_at', 'desc')
                ->take(5)
                ->get();
        });

        return view('dashboard.index', compact('stats', 'platformStats', 'quotas', 'recentActivity', 'upcomingPosts', 'agency', 'agentHealthSummary', 'recentAgentActivity'));
    }

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
}
