<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AiProviderKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiProviderKeyFactory extends Factory
{
    protected $model = AiProviderKey::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'provider_name' => $this->faker->randomElement(['openai', 'anthropic', 'google', 'mistral', 'groq']),
            'api_key' => 'sk-' . $this->faker->sha256(),
            'api_base_url' => null,
            'is_active' => true,
            'priority' => $this->faker->numberBetween(1, 5),
            'notes' => $this->faker->sentence(),
        ];
    }

    public function forProvider(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => $name,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
