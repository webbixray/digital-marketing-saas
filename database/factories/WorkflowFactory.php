<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'agency_id' => Agency::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.uniqid(),
            'status' => 'draft',
            'trigger_type' => fake()->randomElement(['post_published', 'comment_received', 'schedule']),
            'trigger_config' => [],
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Test']],
            ],
            'conditions' => [],
            'execution_count' => 0,
            'last_executed_at' => null,
            'error_message' => null,
            'is_system' => false,
        ];
    }
}
