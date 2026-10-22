<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialPostFactory extends Factory
{
    protected $model = SocialPost::class;

    public function definition(): array
    {
        $platform = fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest']);
        $status = fake()->randomElement(['draft', 'scheduled', 'published', 'failed']);

        return [
            'agency_id' => Agency::factory(),
            'social_account_id' => SocialAccount::factory(),
            'platform' => $platform,
            'content' => fake()->paragraph(),
            'media' => null,
            'links' => null,
            'hashtags' => fake()->words(rand(1, 5)),
            'mentions' => null,
            'tags' => null,
            'status' => $status,
            'scheduled_at' => $status === 'scheduled' ? now()->addDays(rand(1, 7)) : null,
            'published_at' => $status === 'published' ? now()->subDays(rand(1, 30)) : null,
            'failed_at' => $status === 'failed' ? now()->subDays(rand(1, 7)) : null,
            'error_message' => $status === 'failed' ? 'API error: rate limit exceeded' : null,
            'retry_count' => 0,
            'platform_response' => null,
            'external_post_id' => $status === 'published' ? fake()->uuid() : null,
            'views_count' => $status === 'published' ? rand(100, 10000) : 0,
            'likes_count' => $status === 'published' ? rand(10, 1000) : 0,
            'comments_count' => $status === 'published' ? rand(0, 100) : 0,
            'shares_count' => $status === 'published' ? rand(0, 50) : 0,
            'clicks_count' => $status === 'published' ? rand(0, 200) : 0,
            'engagement_rate' => $status === 'published' ? (rand(100, 5000) / 100) : 0.0,
            'metrics' => null,
            'quality_score' => rand(0, 100),
            'is_pinned' => false,
        ];
    }
}
