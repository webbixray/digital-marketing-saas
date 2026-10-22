<?php

namespace App\ClientPortal;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientApproval;
use App\Models\ClientNotification;
use App\Models\ClientPortalSetting;
use App\Models\Invoice;
use App\Models\SocialPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientPortalService
{
    /**
     * @return array{
     *     client: Client,
     *     totalClients: int,
     *     activeClients: int,
     *     activeCampaigns: int,
     *     totalCampaigns: int,
     *     totalSpend: float,
     *     pendingSpend: float,
     *     performanceScore: int,
     *     performanceData: object|null,
     *     paidInvoices: int,
     *     overdueInvoices: int,
     *     recentCampaigns: \Illuminate\Database\Eloquent\Collection<int, Campaign>,
     *     monthlySpend: array<string, float>,
     *     portalSettings: ClientPortalSetting|null
     * }
     */
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

        $totalSpend = (float) Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->sum('total');
        $pendingSpend = (float) Invoice::where('agency_id', $agencyId)
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

        /** @var \stdClass|null $performanceData */
        $performanceScore = $this->calculatePerformanceScore($performanceData);

        $paidInvoices = Invoice::where('agency_id', $agencyId)->where('status', 'paid')->count();
        $overdueInvoices = Invoice::where('agency_id', $agencyId)->where('status', 'overdue')->count();

        $recentCampaigns = Campaign::where('agency_id', $agencyId)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $monthExpr = $this->getMonthExpression('paid_date');
        /** @var array<string, float> $monthlySpend */
        $monthlySpend = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->where('paid_date', '>=', now()->subMonths(6))
            ->select(
                DB::raw("(DATE_FORMAT(`paid_date`, '%Y-%m')) as month"),
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

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     campaigns: LengthAwarePaginator<int, Campaign>,
     *     clients: \Illuminate\Database\Eloquent\Collection<int, Client>,
     *     statusCounts: array<string, int>,
     *     client: Client
     * }
     */
    public function getCampaigns(int $clientId, array $filters = []): array
    {
        $client = Client::findOrFail($clientId);
        $agencyId = $client->agency_id;

        $query = Campaign::where('agency_id', $agencyId)
            ->with(['client', 'posts'])
            ->withCount('posts');

        if (! empty($filters['status']) && in_array($filters['status'], ['draft', 'active', 'paused', 'completed'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['client_id'])) {
            $filterClientId = (int) $filters['client_id'];
            $filterClient = Client::where('agency_id', $agencyId)->find($filterClientId);
            if ($filterClient) {
                $query->where('client_id', $filterClientId);
            }
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
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

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     invoices: LengthAwarePaginator<int, Invoice>,
     *     clients: \Illuminate\Database\Eloquent\Collection<int, Client>,
     *     statusCounts: array<string, int>,
     *     totalOutstanding: float,
     *     totalPaid: float,
     *     overdueCount: int,
     *     client: Client
     * }
     */
    public function getInvoices(int $clientId, array $filters = []): array
    {
        $client = Client::findOrFail($clientId);
        $agencyId = $client->agency_id;

        $query = Invoice::where('agency_id', $agencyId)->with('client');

        if (! empty($filters['status']) && in_array($filters['status'], ['draft', 'pending', 'paid', 'overdue', 'cancelled'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['client_id'])) {
            $filterClientId = (int) $filters['client_id'];
            $filterClient = Client::where('agency_id', $agencyId)->find($filterClientId);
            if ($filterClient) {
                $query->where('client_id', $filterClientId);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $totalOutstanding = (float) Invoice::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total');
        $totalPaid = (float) Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->sum('total');
        $overdueCount = Invoice::where('agency_id', $agencyId)
            ->where('status', 'overdue')
            ->count();

        /** @var array<string, int> $statusCounts */
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

    /**
     * @return array{
     *     campaignPerformance: \Illuminate\Database\Eloquent\Collection<int, Campaign>,
     *     platformMetrics: Collection<int, object>,
     *     monthlyTrend: Collection<int, object>,
     *     topPosts: \Illuminate\Database\Eloquent\Collection<int, SocialPost>,
     *     overallMetrics: array{
     *         total_impressions: int,
     *         total_engagements: int,
     *         avg_engagement_rate: float,
     *         total_clicks: int,
     *         total_posts: int,
     *         published_posts: int
     *     },
     *     client: Client
     * }
     */
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

        /** @var Collection<int, object> $platformMetrics */
        $platformMetrics = (new Collection(SocialPost::where('agency_id', $agencyId)
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
            ->get()
            ->all()))->map(fn ($item) => (object) $item);

        $monthExpr = $this->getMonthExpression('created_at');
        /** @var Collection<int, object> $monthlyTrend */
        $monthlyTrend = (new Collection(SocialPost::where('agency_id', $agencyId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("(DATE_FORMAT(`created_at`, '%Y-%m')) as month"),
                DB::raw('COUNT(*) as post_count'),
                DB::raw('SUM(views_count) as total_views'),
                DB::raw('SUM(likes_count) as total_likes'),
                DB::raw('SUM(clicks_count) as total_clicks'),
                DB::raw('AVG(engagement_rate) as avg_engagement')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->all()))->map(fn ($item) => (object) $item);

        $topPosts = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->orderByDesc('engagement_rate')
            ->take(5)
            ->get();

        $totalLikes = (int) SocialPost::where('agency_id', $agencyId)->sum('likes_count');
        $totalComments = (int) SocialPost::where('agency_id', $agencyId)->sum('comments_count');
        $totalShares = (int) SocialPost::where('agency_id', $agencyId)->sum('shares_count');
        $avgEngagementRate = (float) (SocialPost::where('agency_id', $agencyId)->avg('engagement_rate') ?? 0);
        $totalClicks = (int) SocialPost::where('agency_id', $agencyId)->sum('clicks_count');
        $totalPosts = (int) SocialPost::where('agency_id', $agencyId)->count();
        $publishedPosts = (int) SocialPost::where('agency_id', $agencyId)->where('status', 'published')->count();

        $overallMetrics = [
            'total_impressions' => (int) SocialPost::where('agency_id', $agencyId)->sum('views_count'),
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

    /**
     * @return array{settings: ClientPortalSetting, client: Client}
     */
    public function getSettings(int $clientId): array
    {
        $client = Client::findOrFail($clientId);
        $settings = ClientPortalSetting::firstOrCreate(
            ['agency_id' => $client->agency_id],
            [
                'brand_name' => $client->agency->name ?? 'Unknown Agency',
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

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(int $clientId, array $data): ClientPortalSetting
    {
        $client = Client::findOrFail($clientId);
        $settings = ClientPortalSetting::updateOrCreate(
            ['agency_id' => $client->agency_id],
            $data
        );

        return $settings;
    }

    /**
     * @return array{activities: \Illuminate\Database\Eloquent\Collection<int, ClientNotification>, client: Client}
     */
    public function getActivityFeed(int $clientId, int $limit = 50): array
    {
        $client = Client::findOrFail($clientId);
        $notifications = ClientNotification::forClient($client->id)
            ->recent()
            ->take($limit)
            ->get();

        return ['activities' => $notifications, 'client' => $client];
    }

    /**
     * @return array{approvals: LengthAwarePaginator<int, ClientApproval>, client: Client}
     */
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

        return $approval->fresh() ?? throw new \RuntimeException('Failed to refresh approval');
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

        return $approval->fresh() ?? throw new \RuntimeException('Failed to refresh approval');
    }

    private function calculatePerformanceScore(?\stdClass $data): int
    {
        if (! $data || $data->total_posts == 0) {
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
