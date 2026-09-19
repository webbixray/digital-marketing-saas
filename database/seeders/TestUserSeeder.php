<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create test agency for E2E tests
        $agency = Agency::firstOrCreate(
            ['slug' => 'test-agency'],
            [
                'name' => 'Test Agency',
                'email' => 'test@agency.com',
                'subscription_plan' => 'free',
                'status' => 'active',
            ]
        );

        // Create E2E test user with known credentials
        User::firstOrCreate(
            ['email' => 'test@agency.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'agency_id' => $agency->id,
                'role' => 'owner',
                'is_active' => true,
                'is_approved' => true,
            ]
        );
    }
}
