<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowVersionFactory extends Factory
{
    protected $model = WorkflowVersion::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'version_number' => fake()->numberBetween(1, 10),
            'name' => fake()->words(3, true),
            'trigger_type' => fake()->randomElement(['new_post', 'post_published', 'comment_received', 'schedule', 'webhook']),
            'trigger_config' => [],
            'actions' => [],
            'change_notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
