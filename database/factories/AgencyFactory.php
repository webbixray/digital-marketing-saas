<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'slug' => Str::slug($name).'-'.uniqid(),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'logo' => null,
            'website' => fake()->url(),
            'description' => fake()->sentence(),
            'timezone' => fake()->timezone(),
            'currency' => 'USD',
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'status' => 'active',
            'subscription_plan' => 'starter',
            'subscription_start' => now(),
            'subscription_end' => null,
            'subscription_status' => 'active',
            'subscription_payment_method' => null,
            'customer_id' => null,
            'subscription_id' => null,
            'posts_count' => 0,
            'ai_requests_count' => 0,
            'ai_generations_count' => 0,
            'campaigns_count' => 0,
            'clients_count' => 0,
            'users_count' => 0,
            'social_accounts_count' => 0,
            'landing_pages_count' => 0,
            'forms_count' => 0,
            'custom_settings' => null,
            'branding' => null,
        ];
    }

    public function free(): static
    {
        return $this->state(['subscription_plan' => 'free']);
    }

    public function enterprise(): static
    {
        return $this->state(['subscription_plan' => 'enterprise']);
    }
}
