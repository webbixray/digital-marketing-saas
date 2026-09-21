<?php

namespace App\Services\AI;

use Illuminate\Support\Carbon;

class OptimizationResult
{
    public function __construct(
        public readonly int $campaignId,
        public readonly array $appliedChanges,
        public readonly array $pendingApproval,
        public readonly float $predictedImprovement,
        public readonly Carbon $timestamp,
    ) {}

    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'applied_changes' => $this->appliedChanges,
            'pending_approval' => array_map(fn($s) => $s->toArray(), $this->pendingApproval),
            'predicted_improvement' => $this->predictedImprovement,
            'timestamp' => $this->timestamp->toISOString(),
        ];
    }
}
