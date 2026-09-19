<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientReportFactory extends Factory
{
    protected $model = ClientReport::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'client_id' => Client::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->slug(),
            'report_data' => [
                'summary' => ['total_posts' => 0, 'total_engagement' => 0, 'total_impressions' => 0, 'avg_engagement_rate' => 0],
                'platform_breakdown' => [],
                'top_posts' => [],
                'campaigns' => [],
                'generated_at' => now()->toISOString(),
            ],
            'period' => fake()->randomElement(['monthly', 'quarterly', 'yearly']),
            'start_date' => fake()->dateTimeBetween('-1 year'),
            'end_date' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'access_token' => fake()->uuid(),
            'published_at' => null,
        ];
    }
}
