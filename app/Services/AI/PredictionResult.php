<?php

namespace App\Services\AI;

class PredictionResult
{
    public function __construct(
        public readonly float $predictedEngagementRate,
        public readonly float $confidence,
        public readonly array $factors,
        public readonly array $improvementSuggestions,
    ) {}

    public function toArray(): array
    {
        return [
            'predicted_engagement_rate' => $this->predictedEngagementRate,
            'confidence' => $this->confidence,
            'factors' => $this->factors,
            'improvement_suggestions' => $this->improvementSuggestions,
        ];
    }
}
