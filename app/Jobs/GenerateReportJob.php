<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\Agency;
use App\Models\SocialPost;
use App\Models\Campaign;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(
        public readonly Report $report,
    ) {}

    public function handle(): void
    {
        $this->report->update(['status' => 'processing']);

        try {
            $reportData = $this->generate();

            $this->report->update([
                'status' => 'completed',
                'report_data' => $reportData,
                'last_generated_at' => now(),
            ]);

            Log::info('Report generated successfully', [
                'report_id' => $this->report->id,
                'agency_id' => $this->report->agency_id,
            ]);
        } catch (Throwable $e) {
            $this->report->update(['status' => 'failed']);

            Log::error('Report generation failed', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->report->update(['status' => 'failed']);

        Log::error('GenerateReportJob failed permanently', [
            'report_id' => $this->report->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function generate(): array
    {
        $agency = $this->report->agency;
        $reportType = $this->report->type;

        return match($reportType) {
            'social_media' => $this->generateSocialMediaReport($agency),
            'campaign' => $this->generateCampaignReport($agency),
            'engagement' => $this->generateEngagementReport($agency),
            'audience' => $this->generateAudienceReport($agency),
            'competitor' => $this->generateCompetitorReport($agency),
            default => $this->generateSummaryReport($agency),
        };
    }

    private function generateSocialMediaReport(Agency $agency): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->whereBetween('published_at', [
                $this->report->date_range_start ?? now()->subDays(30),
                $this->report->date_range_end ?? now(),
            ])
            ->get();

        return [
            'summary' => [
                'total_posts' => $posts->count(),
                'total_reach' => $posts->sum('reach'),
                'total_engagement' => $posts->sum('engagement_rate'),
                'avg_engagement_rate' => $posts->avg('engagement_rate') ?? 0,
            ],
            'platform_breakdown' => $posts->groupBy('platform')->map(fn($group) => [
                'posts' => $group->count(),
                'avg_engagement' => round($group->avg('engagement_rate') ?? 0, 2),
                'total_reach' => $group->sum('reach'),
            ])->toArray(),
            'top_posts' => $posts->sortByDesc('engagement_rate')->take(5)->toArray(),
            'campaigns' => [],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function generateCampaignReport(Agency $agency): array
    {
        $campaigns = Campaign::where('agency_id', $agency->id)
            ->withCount('socialPosts')
            ->orderByDesc('created_at')
            ->get();

        return [
            'summary' => [
                'total_campaigns' => $campaigns->count(),
                'active_campaigns' => $campaigns->where('status', 'active')->count(),
                'total_posts' => $campaigns->sum('social_posts_count'),
            ],
            'platform_breakdown' => [],
            'top_posts' => [],
            'campaigns' => $campaigns->toArray(),
            'generated_at' => now()->toISOString(),
        ];
    }

    private function generateEngagementReport(Agency $agency): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->orderByDesc('engagement_rate')
            ->limit(100)
            ->get();

        return [
            'summary' => [
                'total_posts' => $posts->count(),
                'avg_engagement_rate' => $posts->avg('engagement_rate') ?? 0,
                'max_engagement_rate' => $posts->max('engagement_rate') ?? 0,
                'min_engagement_rate' => $posts->min('engagement_rate') ?? 0,
            ],
            'platform_breakdown' => $posts->groupBy('platform')->map(fn($group) => [
                'avg_engagement' => round($group->avg('engagement_rate') ?? 0, 2),
                'post_count' => $group->count(),
            ])->toArray(),
            'top_posts' => $posts->take(10)->toArray(),
            'campaigns' => [],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function generateAudienceReport(Agency $agency): array
    {
        $accounts = $agency->socialAccounts;

        return [
            'summary' => [
                'total_followers' => $accounts->sum('follower_count'),
                'avg_growth_rate' => $accounts->avg('follower_growth_rate') ?? 0,
                'platforms_count' => $accounts->count(),
            ],
            'platform_breakdown' => $accounts->map(fn($a) => [
                'platform' => $a->platform,
                'followers' => $a->follower_count,
                'growth_rate' => $a->follower_growth_rate,
            ])->toArray(),
            'top_posts' => [],
            'campaigns' => [],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function generateCompetitorReport(Agency $agency): array
    {
        // This would integrate with competitor tracking data
        return [
            'summary' => [
                'tracked_competitors' => 0,
                'your_avg_engagement' => 0,
                'industry_avg_engagement' => 0,
            ],
            'platform_breakdown' => [],
            'top_posts' => [],
            'campaigns' => [],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function generateSummaryReport(Agency $agency): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)->where('status', 'published');
        $campaigns = Campaign::where('agency_id' => $agency->id);
        $clients = Client::where('agency_id' => $agency->id);

        return [
            'summary' => [
                'total_posts' => $posts->count(),
                'total_campaigns' => $campaigns->count(),
                'total_clients' => $clients->count(),
                'avg_engagement_rate' => $posts->avg('engagement_rate') ?? 0,
                'total_reach' => $posts->sum('reach'),
            ],
            'platform_breakdown' => [],
            'top_posts' => [],
            'campaigns' => [],
            'generated_at' => now()->toISOString(),
        ];
    }
}
