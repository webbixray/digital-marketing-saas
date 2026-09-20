<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $action = fake()->randomElement(['created', 'updated', 'deleted', 'restored', 'logged_in', 'logged_out', 'exported', 'imported', 'approved', 'rejected']);
        $modelType = fake()->randomElement(['SocialPost', 'SocialAccount', 'CustomTemplate', 'User', 'Agency', 'Invoice', 'Report']);
        $oldValues = $action === 'updated' ? ['status' => 'draft'] : null;
        $newValues = in_array($action, ['created', 'updated']) ? ['status' => 'active'] : null;

        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => fake()->numberBetween(1, 1000),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function created(): static
    {
        return $this->state([
            'action' => 'created',
            'old_values' => null,
            'new_values' => ['status' => 'active', 'name' => fake()->word()],
        ]);
    }

    public function updated(): static
    {
        return $this->state([
            'action' => 'updated',
            'old_values' => ['status' => 'draft', 'name' => fake()->word()],
            'new_values' => ['status' => 'published', 'name' => fake()->word()],
        ]);
    }

    public function deleted(): static
    {
        return $this->state([
            'action' => 'deleted',
            'old_values' => ['status' => 'active', 'deleted_at' => null],
            'new_values' => ['deleted_at' => now()->toISOString()],
        ]);
    }

    public function restored(): static
    {
        return $this->state([
            'action' => 'restored',
            'old_values' => ['deleted_at' => now()->subDay()->toISOString()],
            'new_values' => ['deleted_at' => null],
        ]);
    }

    public function loggedIn(): static
    {
        return $this->state([
            'action' => 'logged_in',
            'old_values' => null,
            'new_values' => ['login_at' => now()->toISOString()],
        ]);
    }

    public function loggedOut(): static
    {
        return $this->state([
            'action' => 'logged_out',
            'old_values' => null,
            'new_values' => ['logout_at' => now()->toISOString()],
        ]);
    }

    public function forModel(string $modelType, int $modelId = null): static
    {
        return $this->state([
            'model_type' => $modelType,
            'model_id' => $modelId ?? fake()->numberBetween(1, 1000),
        ]);
    }

    public function byUser(User $user = null): static
    {
        return $this->state([
            'user_id' => $user ?? User::factory(),
        ]);
    }

    public function withOldValues(array $values): static
    {
        return $this->state(['old_values' => $values]);
    }

    public function withNewValues(array $values): static
    {
        return $this->state(['new_values' => $values]);
    }
}
