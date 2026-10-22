<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['social', 'email', 'campaign', 'analytics', 'custom']),
            'format' => fake()->randomElement(['pdf', 'csv', 'xlsx']),
            'schedule' => fake()->randomElement(['once', 'daily', 'weekly', 'monthly']),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'filters' => null,
            'columns' => null,
        ];
    }
}
