<?php

namespace App\Observers;

use App\Models\Campaign;
use App\Services\Analytics\AnalyticsService;
use App\Services\QuotaService;

class CampaignObserver
{
    public function __construct(
        private QuotaService $quota,
        private AnalyticsService $analytics
    ) {}

    public function created(Campaign $campaign): void
    {
        $campaign->agency?->increment('campaigns_count');
        $campaign->agency && $this->analytics->clearCache($campaign->agency);
    }

    public function deleted(Campaign $campaign): void
    {
        $campaign->agency?->decrement('campaigns_count');
        $campaign->agency && $this->analytics->clearCache($campaign->agency);
    }

    public function updated(Campaign $campaign): void
    {
        if ($campaign->isDirty(['status', 'agency_id'])) {
            $campaign->agency && $this->analytics->clearCache($campaign->agency);
        }
    }
}
