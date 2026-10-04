<?php

namespace App\Services\AI;

use Illuminate\Support\Carbon;

class CrisisAlert
{
    /**
     * @param  array<int, string>  $affectedPlatforms
     * @param  array<int, string>  $recommendedActions
     */
    public function __construct(
        public readonly int $agencyId,
        public readonly string $severity,
        public readonly string $description,
        public readonly array $affectedPlatforms,
        public readonly array $recommendedActions,
        public readonly Carbon $detectedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agency_id' => $this->agencyId,
            'severity' => $this->severity,
            'description' => $this->description,
            'affected_platforms' => $this->affectedPlatforms,
            'recommended_actions' => $this->recommendedActions,
            'detected_at' => $this->detectedAt->toISOString(),
        ];
    }
}
