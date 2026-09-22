<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPortalSetting;
use App\Models\Invoice;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientPortal2Controller extends Controller
{
    /**
     * Get the agency ID for the current authenticated user
     */
    private function getAgencyId(): int
    {
        return auth()->user()->agency_id;
    }

    /**
     * Format a date column as YYYY-MM in a database-agnostic way
     */
    private function getMonthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return "strftime('%Y-%m', {$column})";
        }
        return "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * Show the client portal 2.0 dashboard with summary cards
     */
    public function dashboard(Request $request)
    {
        $agencyId = $this->getAgencyId();

        // Get agency's clients count
        $totalClients = Client::where('agency_id', $agencyId)->count();
        $activeClients = Client::where('agency_id', $agencyId)->where('status', 'active')->count();

        // Campaign summary - scoped to agency
        $activeCampaigns = Campaign::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->count();
        $totalCampaigns = Campaign::where('agency_id', $agencyId)->count();

        // Total spend from invoices - scoped to agency
        $totalSpend = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->sum('total');
        $pendingSpend = Invoice::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total');

        // Performance score - aggregate engagement across all agency campaigns
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

        // Invoice summary
        $paidInvoices = Invoice::where('agency_id', $agencyId)->where('status', 'paid')->count();
        $overdueInvoices = Invoice::where('agency_id', $agencyId)->where('status', 'overdue')->count();

        // Recent campaigns for quick view
        $recentCampaigns = Campaign::where('agency_id', $agencyId)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Monthly spend trend for chart
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

        // Client portal settings
        $portalSettings = ClientPortalSetting::where('agency_id', $agencyId)->first();

        return view('client-portal.dashboard', compact(
            'totalClients', 'activeClients',
            'activeCampaigns', 'totalCampaigns',
            'totalSpend', 'pendingSpend',
            'performanceScore', 'performanceData',
            'paidInvoices', 'overdueInvoices',
            'recentCampaigns', 'monthlySpend',
            'portalSettings'
        ));
    }

    /**
     * Show campaigns list with status
     */
    public function campaigns(Request $request)
    {
        $agencyId = $this->getAgencyId();

        $query = Campaign::where('agency_id', $agencyId)
            ->with(['client', 'posts'])
            ->withCount('posts');

        // Filter by status
        if ($request->filled('status') && in_array($request->status, ['draft', 'active', 'paused', 'completed'])) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $clientId = (int) $request->client_id;
            // Verify client belongs to this agency
            $client = Client::where('agency_id', $agencyId)->find($clientId);
            if ($client) {
                $query->where('client_id', $clientId);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        // Get clients for filter dropdown
        $clients = Client::where('agency_id', $agencyId)->orderBy('name')->get(['id', 'name']);

        // Status counts for filter tabs
        $statusCounts = Campaign::where('agency_id', $agencyId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('client-portal.campaigns', compact(
            'campaigns', 'clients', 'statusCounts'
        ));
    }

    /**
     * Show analytics with embedded charts
     */
    public function analytics(Request $request)
    {
        $agencyId = $this->getAgencyId();

        // Get campaign performance data for chart
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

        // Aggregate metrics by platform
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

        // Monthly performance trend
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

        // Top performing posts
        $topPosts = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->orderByDesc('engagement_rate')
            ->take(5)
            ->get();

        // Overall metrics
        $overallMetrics = [
            'total_impressions' => SocialPost::where('agency_id', $agencyId)->sum('views_count'),
            'total_engagements' => SocialPost::where('agency_id', $agencyId)->sum('likes_count')
                + SocialPost::where('agency_id', $agencyId)->sum('comments_count')
                + SocialPost::where('agency_id', $agencyId)->sum('shares_count'),
            'avg_engagement_rate' => SocialPost::where('agency_id', $agencyId)->avg('engagement_rate') ?? 0,
            'total_clicks' => SocialPost::where('agency_id', $agencyId)->sum('clicks_count'),
            'total_posts' => SocialPost::where('agency_id', $agencyId)->count(),
            'published_posts' => SocialPost::where('agency_id', $agencyId)->where('status', 'published')->count(),
        ];

        return view('client-portal.analytics', compact(
            'campaignPerformance', 'platformMetrics', 'monthlyTrend',
            'topPosts', 'overallMetrics'
        ));
    }

    /**
     * Show invoices list with pay button
     */
    public function invoices(Request $request)
    {
        $agencyId = $this->getAgencyId();

        $query = Invoice::where('agency_id', $agencyId)
            ->with('client');

        // Filter by status
        if ($request->filled('status') && in_array($request->status, ['draft', 'pending', 'paid', 'overdue', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $clientId = (int) $request->client_id;
            $client = Client::where('agency_id', $agencyId)->find($clientId);
            if ($client) {
                $query->where('client_id', $clientId);
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        // Summary stats
        $totalOutstanding = Invoice::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total');
        $totalPaid = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->sum('total');
        $overdueCount = Invoice::where('agency_id', $agencyId)
            ->where('status', 'overdue')
            ->count();

        // Status counts for filter tabs
        $statusCounts = Invoice::where('agency_id', $agencyId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get clients for filter dropdown
        $clients = Client::where('agency_id', $agencyId)->orderBy('name')->get(['id', 'name']);

        return view('client-portal.invoices', compact(
            'invoices', 'clients', 'statusCounts',
            'totalOutstanding', 'totalPaid', 'overdueCount'
        ));
    }

    /**
     * Calculate a performance score from aggregate metrics
     */
    private function calculatePerformanceScore($data): int
    {
        if (!$data || $data->total_posts == 0) {
            return 0;
        }

        // Simple scoring algorithm based on engagement
        $score = min(100, (int) (($data->avg_engagement ?? 0) * 10));
        
        // Boost based on view volume
        if ($data->total_views > 10000) {
            $score = min(100, $score + 10);
        }
        if ($data->total_likes > 1000) {
            $score = min(100, $score + 5);
        }

        return max(0, $score);
    }
}
