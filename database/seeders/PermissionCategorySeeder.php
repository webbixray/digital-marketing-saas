<?php

namespace Database\Seeders;

use App\Models\PermissionCategory;
use Illuminate\Database\Seeder;

class PermissionCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        [
            'name' => 'Content',
            'slug' => 'content',
            'description' => 'Manage content assets, templates, and calendars',
            'icon' => 'fa-folder-open',
            'sort_order' => 10,
            'is_system' => true,
        ],
        [
            'name' => 'Campaigns',
            'slug' => 'campaigns',
            'description' => 'Create and manage marketing campaigns',
            'icon' => 'fa-bullhorn',
            'sort_order' => 20,
            'is_system' => true,
        ],
        [
            'name' => 'Clients',
            'slug' => 'clients',
            'description' => 'Manage client accounts and portals',
            'icon' => 'fa-users',
            'sort_order' => 30,
            'is_system' => true,
        ],
        [
            'name' => 'Analytics',
            'slug' => 'analytics',
            'description' => 'View analytics and generate reports',
            'icon' => 'fa-chart-line',
            'sort_order' => 40,
            'is_system' => true,
        ],
        [
            'name' => 'Billing',
            'slug' => 'billing',
            'description' => 'Manage invoices, subscriptions, and payments',
            'icon' => 'fa-credit-card',
            'sort_order' => 50,
            'is_system' => true,
        ],
        [
            'name' => 'Team',
            'slug' => 'team',
            'description' => 'Manage team members and assignments',
            'icon' => 'fa-user-friends',
            'sort_order' => 60,
            'is_system' => true,
        ],
        [
            'name' => 'Settings',
            'slug' => 'settings',
            'description' => 'Configure agency settings and preferences',
            'icon' => 'fa-cog',
            'sort_order' => 70,
            'is_system' => true,
        ],
        [
            'name' => 'Integrations',
            'slug' => 'integrations',
            'description' => 'Manage third-party integrations and webhooks',
            'icon' => 'fa-plug',
            'sort_order' => 80,
            'is_system' => true,
        ],
        [
            'name' => 'Reports',
            'slug' => 'reports',
            'description' => 'Generate and schedule automated reports',
            'icon' => 'fa-file-alt',
            'sort_order' => 90,
            'is_system' => true,
        ],
        [
            'name' => 'AI',
            'slug' => 'ai',
            'description' => 'Access AI content generation and agents',
            'icon' => 'fa-sparkles',
            'sort_order' => 100,
            'is_system' => true,
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            PermissionCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
