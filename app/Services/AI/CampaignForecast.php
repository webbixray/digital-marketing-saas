<?php

namespace App\Services\AI;

class CampaignForecast
{
    public function __construct(
        public readonly int $campaignId,
        public readonly array $dailyPredictions,
        public readonly string $summary,
        public readonly array $recommendations,
    ) {}

    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'daily_predictions' => $this->dailyPredictions,
            'summary' => $this->summary,
            'recommendations' => $this->recommendations,
        ];
    }
}
