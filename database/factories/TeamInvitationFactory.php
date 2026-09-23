<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamInvitationFactory extends Factory
{
    protected $model = TeamInvitation::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['admin', 'member', 'viewer']),
            'token' => bin2hex(random_bytes(32)),
            'invited_by' => User::factory(),
            'accepted_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function accepted(): static
    {
        return $this->state(['accepted_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
