<?php

namespace App\Services\AI;

class ViralPrediction
{
    public function __construct(
        public readonly int $viralScore,
        public readonly array $factors,
        public readonly array $platformOptimization,
        public readonly string $bestTimeToPost,
    ) {}

    public function toArray(): array
    {
        return [
            'viral_score' => $this->viralScore,
            'factors' => $this->factors,
            'platform_optimization' => $this->platformOptimization,
            'best_time_to_post' => $this->bestTimeToPost,
        ];
    }
}
