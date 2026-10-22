<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\InboxMessage;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class InboxMessageFactory extends Factory
{
    protected $model = InboxMessage::class;

    public function definition(): array
    {
        $platform = fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube']);

        return [
            'agency_id' => Agency::factory(),
            'social_account_id' => SocialAccount::factory(),
            'platform' => $platform,
            'message_id' => fake()->uuid(),
            'message_type' => fake()->randomElement(['comment', 'mention', 'dm', 'reply']),
            'author_id' => fake()->uuid(),
            'author_name' => fake()->name(),
            'author_username' => fake()->userName(),
            'author_avatar' => fake()->imageUrl(),
            'content' => fake()->sentence(),
            'status' => fake()->randomElement(['unread', 'read', 'replied']),
            'received_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'metadata' => null,
        ];
    }
}
