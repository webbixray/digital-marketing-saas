<?php

namespace App\ClientPortal;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientNotification;
use App\Models\ClientPortalSetting;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Models\ClientApproval;
use Illuminate\Support\Facades\DB;

class ClientPortalService
{
    public function getDashboardData(int $clientId): array
    {
        $client = Client::findOrFail($clientId);
        $agencyId = $client->agency_id;

        $totalClients = Client::where('agency_id', $agencyId)->count();
        $activeClients = Client::where('agency_id', $agencyId)->where('status', 'active')->count();

        $activeCampaigns = Campaign::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->count();
        $totalCampaigns = Campaign::where('agency_id', $agencyId)->count();

        $totalSpend = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->sum('total');
        $pendingSpend = Invoice::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total');

        $performanceData = SocialPost::where('agency_id', $agencyId)
            ->select(
                DB::raw('AVG(engagement_rate) as avg_engagement'),
                DB::raw('SUM(views_count) as total_views'),
                DB::raw('SUM(likes_count) as total_likes'),
                DB::raw('SUM(clicks_count) as total_clicks'),
                DB::raw('COUNT(*) as total_posts')
            )
            ->first();

        $performanceScore = $this->calculatePerformanceScore($performanceData);

        $paidInvoices = Invoice::where('agency_id', $agencyId)->where('status', 'paid')->count();
        $overdueInvoices = Invoice::where('agency_id', $agencyId)->where('status', 'overdue')->count();

        $recentCampaigns = Campaign::where('agency_id', $agencyId)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $monthExpr = $this->getMonthExpression('paid_date');
        $monthlySpend = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->where('paid_date', '>=', now()->subMonths(6))
            ->select(
                DB::raw("{$monthExpr} as month"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $portalSettings = ClientPortalSetting::where('agency_id', $agencyId)->first();

        return [
            'client' => $client,
            'totalClients' => $totalClients,
            'activeClients' => $activeClients,
            'activeCampaigns' => $activeCampaigns,
            'totalCampaigns' => $totalCampaigns,
            'totalSpend' => $totalSpend,
            'pendingSpend' => $pendingSpend,
            'performanceScore' => $performanceScore,
            'performanceData' => $performanceData,
            'paidInvoices' => $paidInvoices,
            'overdueInvoices' => $overdueInvoices,
            'recentCampaigns' => $recentCampaigns,
            'monthlySpend' => $monthlySpend,
            'portalSettings' => $portalSettings,
        ];
    }

    public function getCampaigns(int $clientId, array $filters = [])
    {
        $client = Client::findOrFail($clientId);
        $agencyId = $client->agency_id;

        $query = Campaign::where('agency_id', $agencyId)
            ->with(['client', 'posts'])
            ->withCount('posts');

        if (!empty($filters['status']) && in_array($filters['status'], ['draft', 'active', 'paused', 'completed'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['client_id'])) {
            $filterClientId = (int) $filters['client_id'];
            $filterClient = Client::where('agency_id', $agencyId)->find($filterClientId);
            if ($filterClient) {
                $query->where('client_id', $filterClientId);
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $clients = Client::where('agency_id', $agencyId)->orderBy('name')->get(['id', 'name']);

        $statusCounts = Campaign::where('agency_id', $agencyId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'campaigns' => $campaigns,
            'clients' => $clients,
            'statusCounts' => $statusCounts,
            'client' => $client,
        ];
    }

    public function getInvoices(int $clientId, array $filters = [])
    {
        $client = Client::findOrFail($clientId);
        $agencyId = $client->agency_id;

        $query = Invoice::where('agency_id', $agencyId)->with('client');

        if (!empty($filters['status']) && in_array($filters['status'], ['draft', 'pending', 'paid', 'overdue', 'cancelled'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['client_id'])) {
            $filterClientId = (int) $filters['client_id'];
            $filterClient = Client::where('agency_id', $agencyId)->find($filterClientId);
            if ($filterClient) {
                $query->where('client_id', $filterClientId);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $totalOutstanding = Invoice::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total');
        $totalPaid = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->sum('total');
        $overdueCount = Invoice::where('agency_id', $agencyId)
            ->where('status', 'overdue')
            ->count();

        $statusCounts = Invoice::where('agency_id', $agencyId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $clients = Client::where('agency_id', $agencyId)->orderBy('name')->get(['id', 'name']);

        return [
            'invoices' => $invoices,
            'clients' => $clients,
            'statusCounts' => $statusCounts,
            'totalOutstanding' => $totalOutstanding,
            'totalPaid' => $totalPaid,
            'overdueCount' => $overdueCount,
            'client' => $client,
        ];
    }

    public function getAnalytics(int $clientId): array
    {
        $client = Client::findOrFail($clientId);
        $agencyId = $client->agency_id;

        $campaignPerformance = Campaign::where('agency_id', $agencyId)
            ->select(
                'name',
                'status',
                'views_count',
                'likes_count',
                'comments_count',
                'shares_count',
                'clicks_count',
                'engagement_rate',
                'posts_count'
            )
            ->whereIn('status', ['active', 'completed'])
            ->orderByDesc('views_count')
            ->take(10)
            ->get();

        $platformMetrics = SocialPost::where('agency_id', $agencyId)
            ->select(
                'platform',
                DB::raw('COUNT(*) as post_count'),
                DB::raw('SUM(views_count) as total_views'),
                DB::raw('SUM(likes_count) as total_likes'),
                DB::raw('SUM(comments_count) as total_comments'),
                DB::raw('SUM(shares_count) as total_shares'),
                DB::raw('SUM(clicks_count) as total_clicks'),
                DB::raw('AVG(engagement_rate) as avg_engagement')
            )
            ->groupBy('platform')
            ->orderByDesc('total_views')
            ->get();

        $monthExpr = $this->getMonthExpression('created_at');
        $monthlyTrend = SocialPost::where('agency_id', $agencyId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("{$monthExpr} as month"),
                DB::raw('COUNT(*) as post_count'),
                DB::raw('SUM(views_count) as total_views'),
                DB::raw('SUM(likes_count) as total_likes'),
                DB::raw('SUM(clicks_count) as total_clicks'),
                DB::raw('AVG(engagement_rate) as avg_engagement')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $topPosts = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->orderByDesc('engagement_rate')
            ->take(5)
            ->get();

        $totalLikes = SocialPost::where('agency_id', $agencyId)->sum('likes_count');
        $totalComments = SocialPost::where('agency_id', $agencyId)->sum('comments_count');
        $totalShares = SocialPost::where('agency_id', $agencyId)->sum('shares_count');
        $avgEngagementRate = SocialPost::where('agency_id', $agencyId)->avg('engagement_rate') ?? 0;
        $totalClicks = SocialPost::where('agency_id', $agencyId)->sum('clicks_count');
        $totalPosts = SocialPost::where('agency_id', $agencyId)->count();
        $publishedPosts = SocialPost::where('agency_id', $agencyId)->where('status', 'published')->count();

        $overallMetrics = [
            'total_impressions' => SocialPost::where('agency_id', $agencyId)->sum('views_count'),
            'total_engagements' => $totalLikes + $totalComments + $totalShares,
            'avg_engagement_rate' => $avgEngagementRate,
            'total_clicks' => $totalClicks,
            'total_posts' => $totalPosts,
            'published_posts' => $publishedPosts,
        ];

        return [
            'campaignPerformance' => $campaignPerformance,
            'platformMetrics' => $platformMetrics,
            'monthlyTrend' => $monthlyTrend,
            'topPosts' => $topPosts,
            'overallMetrics' => $overallMetrics,
            'client' => $client,
        ];
    }

    public function getSettings(int $clientId): array
    {
        $client = Client::findOrFail($clientId);
        $settings = ClientPortalSetting::firstOrCreate(
            ['agency_id' => $client->agency_id],
            [
                'brand_name' => $client->agency->name,
                'brand_color' => '#6366f1',
                'is_enabled' => true,
                'show_analytics' => true,
                'show_invoices' => true,
                'allow_approvals' => true,
                'show_team_activity' => false,
            ]
        );

        return ['settings' => $settings, 'client' => $client];
    }

    public function updateSettings(int $clientId, array $data): ClientPortalSetting
    {
        $client = Client::findOrFail($clientId);
        $settings = ClientPortalSetting::updateOrCreate(
            ['agency_id' => $client->agency_id],
            $data
        );

        return $settings;
    }

    public function getActivityFeed(int $clientId, int $limit = 50): array
    {
        $client = Client::findOrFail($clientId);
        $notifications = ClientNotification::forClient($client->id)
            ->recent()
            ->take($limit)
            ->get();

        return ['activities' => $notifications, 'client' => $client];
    }

    public function getApprovalQueue(int $clientId): array
    {
        $client = Client::findOrFail($clientId);
        $pendingApprovals = ClientApproval::forClient($client->id)
            ->pending()
            ->with('socialPost')
            ->orderByDesc('created_at')
            ->paginate(10);

        return ['approvals' => $pendingApprovals, 'client' => $client];
    }

    public function approveContent(int $clientId, int $contentId): ClientApproval
    {
        $approval = ClientApproval::forClient($clientId)->findOrFail($contentId);
        $approval->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        if ($approval->socialPost) {
            $approval->socialPost->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => 'client',
            ]);
        }

        return $approval->fresh();
    }

    public function rejectContent(int $clientId, int $contentId, string $reason = ''): ClientApproval
    {
        $approval = ClientApproval::forClient($clientId)->findOrFail($contentId);
        $approval->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        if ($approval->socialPost) {
            $approval->socialPost->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_at' => now(),
                'rejected_by' => 'client',
            ]);
        }

        return $approval->fresh();
    }

    private function calculatePerformanceScore($data): int
    {
        if (!$data || $data->total_posts == 0) {
            return 0;
        }

        $score = min(100, (int) (($data->avg_engagement ?? 0) * 10));

        if ($data->total_views > 10000) {
            $score = min(100, $score + 10);
        }
        if ($data->total_likes > 1000) {
            $score = min(100, $score + 5);
        }

        return max(0, $score);
    }

    private function getMonthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return "strftime('%Y-%m', {$column})";
        }
        return "DATE_FORMAT({$column}, '%Y-%m')";
    }
}
