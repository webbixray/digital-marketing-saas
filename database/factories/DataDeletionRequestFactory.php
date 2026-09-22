<?php

namespace Database\Factories;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataDeletionRequestFactory extends Factory
{
    protected $model = DataDeletionRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'reason' => fake()->sentence(),
            'scheduled_at' => now()->addDays(30),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function scheduledForSoon(): static
    {
        return $this->state(['scheduled_at' => now()->addDays(1)]);
    }

    public function scheduledForLater(): static
    {
        return $this->state(['scheduled_at' => now()->addDays(30)]);
    }
}
