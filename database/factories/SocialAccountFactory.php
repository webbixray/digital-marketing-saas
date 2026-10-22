<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        $platform = fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube']);

        return [
            'agency_id' => Agency::factory(),
            'platform' => $platform,
            'platform_account_id' => fake()->uuid(),
            'platform_username' => fake()->userName(),
            'platform_display_name' => fake()->name(),
            'platform_account_type' => 'page',
            'access_token' => fake()->sha256(),
            'refresh_token' => null,
            'token_expires_at' => null,
            'token_type' => 'Bearer',
            'scope' => null,
            'scope_str' => null,
            'metadata' => null,
            'is_active' => true,
            'is_verified' => true,
        ];
    }

    public function facebook(): static
    {
        return $this->state(['platform' => 'facebook']);
    }

    public function instagram(): static
    {
        return $this->state(['platform' => 'instagram']);
    }

    public function twitter(): static
    {
        return $this->state(['platform' => 'twitter']);
    }

    public function youtube(): static
    {
        return $this->state(['platform' => 'youtube']);
    }

    public function tiktok(): static
    {
        return $this->state(['platform' => 'tiktok']);
    }
}
