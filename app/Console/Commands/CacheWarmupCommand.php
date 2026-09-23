<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Services\Analytics\AnalyticsService;
use App\Services\Calendar\ContentCalendarService;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Jobs\Email\SendEmailCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheWarmupCommand extends Command
{
    protected $signature = 'cache:warmup
                            {agency_id? : Specific agency ID to warm up}
                            {--all : Warm up cache for all agencies}
                            {--queue : Dispatch warming as queued jobs}';

    protected $description = 'Pre-warm analytics, calendar, and campaign caches for optimal performance';

    public function handle(
        AnalyticsService $analytics,
        ContentCalendarService $calendar,
    ): int {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — Cache Warmup');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $agencyId = $this->argument('agency_id');
        $warmAll = $this->option('all');
        $useQueue = $this->option('queue');

        if ($agencyId) {
            $agencies = Agency::where('id', $agencyId)->get();
        } elseif ($warmAll) {
            $agencies = Agency::all();
        } else {
            // Warm active agencies only (last 30 days)
            $agencies = Agency::whereHas('activityLogs', function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            })->get();
        }

        $totalAgencies = $agencies->count();
        $this->info("Warming cache for {$totalAgencies} agencies...");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalAgencies);
        $bar->start();

        foreach ($agencies as $agency) {
            try {
                if ($useQueue) {
                    dispatch(function () use ($analytics, $calendar, $agency) {
                        $this->warmAgencyCache($analytics, $calendar, $agency);
                    })->onQueue('default');
                } else {
                    $this->warmAgencyCache($analytics, $calendar, $agency);
                }
            } catch (\Exception $e) {
                Log::warning("Cache warmup failed for agency {$agency->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Cache warmup completed for all agencies!');
        $this->info('═══════════════════════════════════════════════════');

        return self::SUCCESS;
    }

    private function warmAgencyCache(
        AnalyticsService $analytics,
        ContentCalendarService $calendar,
        Agency $agency,
    ): void {
        // Warm analytics cache
        $analytics->getDashboardStats($agency, 30);
        $analytics->getOverviewStats($agency);
        $analytics->getSocialStats($agency);
        $analytics->getEngagementStats($agency);
        $analytics->getCampaignStats($agency);
        $analytics->getClientStats($agency);

        // Warm calendar cache
        $calendar->warmCache($agency->id);

        // Warm AI stats
        $analytics->getAiStats($agency);
    }
}
