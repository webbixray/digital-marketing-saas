<?php

namespace Database\Factories;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailCampaignRecipientFactory extends Factory
{
    protected $model = EmailCampaignRecipient::class;

    public function definition(): array
    {
        return [
            'email_campaign_id' => EmailCampaign::factory(),
            'email' => fake()->safeEmail(),
            'name' => fake()->name(),
            'status' => 'pending',
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function opened(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'opened',
            'sent_at' => now(),
            'opened_at' => now(),
        ]);
    }

    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'clicked',
            'sent_at' => now(),
            'opened_at' => now(),
            'clicked_at' => now(),
        ]);
    }
}
