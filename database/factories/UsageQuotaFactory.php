<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\UsageQuota;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageQuotaFactory extends Factory
{
    protected $model = UsageQuota::class;

    public function definition(): array
    {
        $metrics = ['ai_tokens', 'storage_gb', 'api_calls', 'sms_sent', 'emails_sent'];
        $limit = fake()->numberBetween(1000, 100000);
        $used = fake()->numberBetween(0, (int) ($limit * 0.7));

        return [
            'agency_id' => Agency::factory(),
            'metric' => 'ai_tokens',
            'limit' => 10000,
            'used' => 0,
            'period' => 'monthly',
            'reset_at' => now()->startOfMonth()->addMonth(),
        ];
    }

    public function forMetric(string $metric): static
    {
        return $this->state(['metric' => $metric]);
    }

    public function exceeded(): static
    {
        $limit = fake()->numberBetween(1000, 50000);

        return $this->state([
            'limit' => $limit,
            'used' => $limit + fake()->numberBetween(100, 1000),
        ]);
    }

    public function yearly(): static
    {
        return $this->state(['period' => 'yearly']);
    }
}
