<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\EmailCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmailCampaignFactory extends Factory
{
    protected $model = EmailCampaign::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->words(3, true),
            'slug' => function (array $attributes) {
                return Str::slug($attributes['name'] ?? 'campaign').'-'.Str::random(6);
            },
            'type' => fake()->randomElement(['newsletter', 'promotional', 'transactional']),
            'status' => 'draft',
            'subject' => fake()->sentence(),
            'from_name' => fake()->name(),
            'from_email' => fake()->safeEmail(),
            'reply_to' => fake()->safeEmail(),
            'content' => fake()->paragraphs(3, true),
            'tags' => [fake()->word(), fake()->word()],
            'recipients_count' => 0,
            'sent_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'bounced_count' => 0,
            'unsubscribed_count' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
            'bounce_rate' => 0,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => 100,
            'sent_count' => 95,
            'opened_count' => 45,
            'clicked_count' => 20,
            'bounced_count' => 5,
            'open_rate' => 47.37,
            'click_rate' => 21.05,
            'bounce_rate' => 5.26,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }
}
