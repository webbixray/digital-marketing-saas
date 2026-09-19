<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            FeatureFlagSeeder::class,
            WorkflowTemplateSeeder::class,
            PlatformSeeder::class,
            RolePermissionSeeder::class,
            DemoSeeder::class,
            TestUserSeeder::class,
        ]);
    }
}
