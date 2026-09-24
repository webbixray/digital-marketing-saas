<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientApproval;
use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientApprovalFactory extends Factory
{
    protected $model = ClientApproval::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'social_post_id' => SocialPost::factory(),
            'status' => 'pending',
            'reviewed_at' => null,
            'rejection_reason' => null,
            'created_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'reviewed_at' => null,
        ]);
    }
}
