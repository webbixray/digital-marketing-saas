<?php

namespace App\Services\AI;

class Simulation
{
    public function __construct(
        public readonly array $recommendation,
        public readonly string $category,
        public readonly float $predictedEngagementChange,
        public readonly float $predictedReachChange,
        public readonly float $confidence,
        public readonly string $riskLevel,
    ) {}

    public function toArray(): array
    {
        return [
            'recommendation' => $this->recommendation,
            'category' => $this->category,
            'predicted_engagement_change' => $this->predictedEngagementChange,
            'predicted_reach_change' => $this->predictedReachChange,
            'confidence' => $this->confidence,
            'risk_level' => $this->riskLevel,
        ];
    }
}
