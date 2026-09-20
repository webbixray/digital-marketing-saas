<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\CustomTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomTemplateFactory extends Factory
{
    protected $model = CustomTemplate::class;

    public function definition(): array
    {
        $name = fake()->words(rand(2, 4), true);
        $type = fake()->randomElement(['email', 'sms', 'social', 'landing', 'notification']);

        return [
            'agency_id' => Agency::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'type' => $type,
            'content' => fake()->paragraphs(rand(2, 5), true),
            'variables' => fake()->randomElements(['name', 'email', 'company', 'phone', 'date', 'url', 'amount', 'currency'], rand(2, 5)),
            'is_active' => fake()->boolean(80),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function active(): static
    {
        return $this->state([
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function email(): static
    {
        return $this->state([
            'type' => 'email',
            'content' => fake()->paragraphs(3, true)."\n\nRegards,\n{{company}} Team",
            'variables' => fake()->randomElements(['name', 'email', 'company', 'date', 'url'], rand(2, 4)),
        ]);
    }

    public function sms(): static
    {
        return $this->state([
            'type' => 'sms',
            'content' => fake()->sentence(10).' - {{company}}',
            'variables' => fake()->randomElements(['name', 'company', 'phone'], rand(1, 3)),
        ]);
    }

    public function social(): static
    {
        return $this->state([
            'type' => 'social',
            'content' => fake()->sentence(rand(10, 20)).' '.fake()->words(rand(2, 5), true),
            'variables' => fake()->randomElements(['name', 'company', 'url', 'date'], rand(1, 3)),
        ]);
    }

    public function landing(): static
    {
        return $this->state([
            'type' => 'landing',
            'content' => '<h1>'.fake()->sentence().'</h1><p>'.fake()->paragraphs(2, true).'</p>',
            'variables' => fake()->randomElements(['name', 'email', 'company', 'phone', 'url'], rand(2, 4)),
        ]);
    }

    public function notification(): static
    {
        return $this->state([
            'type' => 'notification',
            'content' => fake()->sentence(8),
            'variables' => fake()->randomElements(['name', 'company', 'date'], rand(1, 3)),
        ]);
    }

    public function ordered(int $order): static
    {
        return $this->state([
            'sort_order' => $order,
        ]);
    }
}
