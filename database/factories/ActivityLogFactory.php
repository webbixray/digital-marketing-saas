<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'published', 'scheduled']),
            'description' => fake()->sentence(),
            'subject_type' => null,
            'subject_id' => null,
            'metadata' => null,
            'created_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
