<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StagingSeeder extends Seeder
{
    public function run(): void
    {
        // Create staging plans
        $plans = [
            ['name' => 'Free', 'slug' => 'free', 'price' => 0, 'posts_limit' => 10, 'ai_generations_limit' => 5, 'social_accounts_limit' => 2],
            ['name' => 'Starter', 'slug' => 'starter', 'price' => 29, 'posts_limit' => 100, 'ai_generations_limit' => 50, 'social_accounts_limit' => 5],
            ['name' => 'Pro', 'slug' => 'pro', 'price' => 79, 'posts_limit' => 500, 'ai_generations_limit' => 200, 'social_accounts_limit' => 15],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 199, 'posts_limit' => -1, 'ai_generations_limit' => -1, 'social_accounts_limit' => -1],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }

        // Create staging admin user
        $agency = Agency::firstOrCreate(
            ['slug' => 'staging-agency'],
            [
                'name' => 'Staging Agency',
                'email' => 'staging@digitalmarketingsaas.com',
                'status' => 'active',
                'subscription_plan' => 'enterprise',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@digitalmarketingsaas.com'],
            [
                'name' => 'Staging Admin',
                'password' => Hash::make('staging123'),
                'agency_id' => $agency->id,
            ]
        );

        $this->command->info('Staging data seeded successfully.');
        $this->command->info('Admin login: admin@digitalmarketingsaas.com / staging123');
    }
}
