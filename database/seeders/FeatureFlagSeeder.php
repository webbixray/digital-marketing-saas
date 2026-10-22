<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'feature_key' => 'ai_assistant',
                'feature_name' => 'AI Assistant',
                'description' => 'Natural language AI assistant for agency management',
                'enabled' => true,
                'required_plan' => 1,
                'allowed_roles' => ['owner', 'admin', 'manager'],
                'settings' => ['max_interactions_per_day' => 100],
            ],
            [
                'feature_key' => 'telegram_bot',
                'feature_name' => 'Telegram Bot',
                'description' => 'Telegram integration for agency commands',
                'enabled' => true,
                'required_plan' => 1,
                'allowed_roles' => ['owner', 'admin', 'manager'],
                'settings' => ['allowed_commands' => ['status', 'posts', 'analytics', 'ai']],
            ],
            [
                'feature_key' => 'social_listening',
                'feature_name' => 'Social Listening',
                'description' => 'Monitor brand mentions across social platforms',
                'enabled' => true,
                'required_plan' => 2,
                'allowed_roles' => ['owner', 'admin'],
                'settings' => ['monitored_platforms' => ['twitter', 'facebook', 'instagram']],
            ],
            [
                'feature_key' => 'content_calendar',
                'feature_name' => 'Content Calendar',
                'description' => 'Visual content calendar for planning posts',
                'enabled' => true,
                'required_plan' => 1,
                'allowed_roles' => ['owner', 'admin', 'manager'],
                'settings' => ['max_scheduled_posts' => 100],
            ],
            [
                'feature_key' => 'advanced_analytics',
                'feature_name' => 'Advanced Analytics',
                'description' => 'Advanced reporting and analytics dashboard',
                'enabled' => true,
                'required_plan' => 2,
                'allowed_roles' => ['owner', 'admin'],
                'settings' => ['export_formats' => ['pdf', 'csv', 'xlsx']],
            ],
        ];

        foreach ($features as $feature) {
            FeatureFlag::updateOrCreate(
                ['feature_key' => $feature['feature_key']],
                $feature
            );
        }
    }
}
