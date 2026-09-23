<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AnalyticsEvent;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'event_type' => fake()->randomElement(['page_view', 'click', 'impression', 'engagement', 'conversion']),
            'event_data' => ['source' => 'web', 'value' => fake()->numberBetween(1, 100)],
            'platform' => fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok']),
            'social_post_id' => SocialPost::factory(),
            'metadata' => ['browser' => 'chrome', 'ip' => fake()->ipv4()],
        ];
    }
}
