<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $statuses = ['active', 'inactive', 'lead'];
        $industries = ['Technology', 'Healthcare', 'Finance', 'Retail', 'Education', 'Real Estate', 'Manufacturing', 'Hospitality'];

        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'company' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'industry' => fake()->randomElement($industries),
            'status' => fake()->randomElement($statuses),
            'notes' => fake()->sentence(),
            'address' => fake()->address(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function lead(): static
    {
        return $this->state(['status' => 'lead']);
    }
}
