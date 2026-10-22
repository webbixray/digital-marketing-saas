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
        $platform = fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin']);
        $status = fake()->randomElement(['unread', 'read', 'replied']);

        return [
            'agency_id' => Agency::factory(),
            'social_account_id' => SocialAccount::factory(),
            'platform' => $platform,
            'message_id' => fake()->uuid(),
            'message_type' => fake()->randomElement(['comment', 'mention', 'direct_message', 'reply']),
            'author_name' => fake()->name(),
            'author_username' => fake()->userName(),
            'author_avatar' => null,
            'content' => fake()->sentence(),
            'status' => $status,
            'received_at' => fake()->dateTimeBetween('-14 days', 'now'),
            'read_at' => $status !== 'unread' ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'replied_at' => $status === 'replied' ? fake()->dateTimeBetween('-3 days', 'now') : null,
            'replied_content' => $status === 'replied' ? fake()->sentence() : null,
        ];
    }
}
