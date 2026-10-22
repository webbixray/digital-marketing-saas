<?php

namespace Database\Factories;

use App\Models\AbTest;
use App\Models\AbTestLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbTestLogFactory extends Factory
{
    protected $model = AbTestLog::class;

    public function definition(): array
    {
        return [
            'ab_test_id' => AbTest::factory(),
            'variant' => fake()->randomElement(['a', 'b']),
            'event' => fake()->randomElement(['impression', 'engagement', 'click']),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
