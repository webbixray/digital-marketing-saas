<?php

namespace Database\Factories;

use App\Models\AgentWorkflowExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentWorkflowExecutionFactory extends Factory
{
    protected $model = AgentWorkflowExecution::class;

    public function definition(): array
    {
        $statuses = ['success', 'running', 'pending', 'failed', 'cancelled'];
        $status = fake()->randomElement($statuses);
        $startedAt = fake()->dateTimeBetween('-14 days', 'now');
        $stepsTotal = fake()->numberBetween(3, 12);
        $stepsCompleted = $status === 'success' ? $stepsTotal : fake()->numberBetween(0, $stepsTotal);

        return [
            'execution_id' => fake()->uuid(),
            'workflow_name' => fake()->randomElement(['content', 'analytics', 'security', 'social', 'support', 'campaign']),
            'agency_id' => \App\Models\Agency::factory(),
            'user_id' => \App\Models\User::factory(),
            'status' => $status,
            'input_data' => ['prompt' => fake()->sentence()],
            'output_data' => $status === 'success' ? ['result' => fake()->sentence()] : null,
            'steps_total' => $stepsTotal,
            'steps_completed' => $stepsCompleted,
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
            'started_at' => $startedAt,
            'completed_at' => in_array($status, ['success', 'failed', 'cancelled']) ? fake()->dateTimeBetween($startedAt, 'now') : null,
            'duration_ms' => fake()->numberBetween(200, 30000),
        ];
    }
}
