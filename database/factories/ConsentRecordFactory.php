<?php

namespace Database\Factories;

use App\Models\ConsentRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsentRecordFactory extends Factory
{
    protected $model = ConsentRecord::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'consent_type' => fake()->randomElement(['marketing', 'analytics', 'third_party']),
            'granted' => fake()->boolean(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function granted(): static
    {
        return $this->state(['granted' => true]);
    }

    public function denied(): static
    {
        return $this->state(['granted' => false]);
    }

    public function withExpiry(int $days = 365): static
    {
        return $this->state([
            'granted' => true,
            'expires_at' => now()->addDays($days),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'granted' => true,
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function marketing(): static
    {
        return $this->state(['consent_type' => 'marketing']);
    }

    public function analytics(): static
    {
        return $this->state(['consent_type' => 'analytics']);
    }

    public function thirdParty(): static
    {
        return $this->state(['consent_type' => 'third_party']);
    }

    public function dataSale(): static
    {
        return $this->state(['consent_type' => 'data_sale']);
    }
}
