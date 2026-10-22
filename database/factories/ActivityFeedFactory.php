<?php

namespace Database\Factories;

use App\Models\ActivityFeed;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFeedFactory extends Factory
{
    protected $model = ActivityFeed::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['post_created', 'post_published', 'campaign_created', 'campaign_sent', 'client_added', 'workflow_executed']),
            'subject_type' => 'App\\Models\\SocialPost',
            'subject_id' => fake()->numberBetween(1, 100),
            'metadata' => null,
        ];
    }
}
