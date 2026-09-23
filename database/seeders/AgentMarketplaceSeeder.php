<?php

namespace Database\Seeders;

use App\Models\AgentMarketplaceCategory;
use App\Models\AgentMarketplaceItem;
use Illuminate\Database\Seeder;

class AgentMarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Content', 'slug' => 'content', 'description' => 'AI agents for content creation, optimization, and repurposing', 'icon' => 'fas fa-pen-nib', 'sort_order' => 10],
            ['name' => 'Analytics', 'slug' => 'analytics', 'description' => 'Agents that analyze performance data and generate insights', 'icon' => 'fas fa-chart-line', 'sort_order' => 20],
            ['name' => 'Campaigns', 'slug' => 'campaigns', 'description' => 'Campaign management and optimization agents', 'icon' => 'fas fa-bullhorn', 'sort_order' => 30],
            ['name' => 'Social Media', 'slug' => 'social-media', 'description' => 'Social media scheduling, posting, and engagement agents', 'icon' => 'fas fa-share-nodes', 'sort_order' => 40],
            ['name' => 'Email', 'slug' => 'email', 'description' => 'Email marketing automation and campaign agents', 'icon' => 'fas fa-envelope', 'sort_order' => 50],
            ['name' => 'SEO', 'slug' => 'seo', 'description' => 'Search engine optimization and keyword research agents', 'icon' => 'fas fa-search', 'sort_order' => 60],
            ['name' => 'Reporting', 'slug' => 'reporting', 'description' => 'Automated reporting and dashboard generation agents', 'icon' => 'fas fa-file-lines', 'sort_order' => 70],
            ['name' => 'Integrations', 'slug' => 'integrations', 'description' => 'Third-party integration and data sync agents', 'icon' => 'fas fa-plug', 'sort_order' => 80],
        ];

        foreach ($categories as $cat) {
            $cat['is_active'] = true;
            AgentMarketplaceCategory::create($cat);
        }

        $content = AgentMarketplaceCategory::where('slug', 'content')->first();
        $analytics = AgentMarketplaceCategory::where('slug', 'analytics')->first();
        $campaigns = AgentMarketplaceCategory::where('slug', 'campaigns')->first();
        $social = AgentMarketplaceCategory::where('slug', 'social-media')->first();
        $seo = AgentMarketplaceCategory::where('slug', 'seo')->first();

        $agents = [
            [
                'name' => 'AI Blog Writer Pro',
                'slug' => 'ai-blog-writer-pro',
                'description' => 'Generate SEO-optimized blog posts, articles, and long-form content in seconds. Supports 30+ languages with tone customization.',
                'category_id' => $content?->id,
                'tags' => ['blog', 'writing', 'seo', 'content'],
                'icon' => 'fas fa-feather-pointed',
                'screenshots' => ['/images/marketplace/blog-writer-1.png', '/images/marketplace/blog-writer-2.png'],
                'demo_url' => 'https://demo.example.com/blog-writer',
                'pricing_type' => 'pricing_tiers',
                'pricing_config' => ['free_tier' => ['posts_per_month' => 5], 'pro_tier' => ['price' => 29, 'posts_per_month' => 100], 'enterprise_tier' => ['price' => 99, 'posts_per_month' => -1]],
                'features' => ['SEO optimization', 'Multi-language support', 'Tone customization', 'Plagiarism check', 'Auto-publish to CMS'],
                'requirements' => ['ai_content feature enabled', 'CMS integration configured'],
                'install_count' => 1250,
                'rating_avg' => 4.7,
                'rating_count' => 342,
                'is_featured' => true,
                'is_approved' => true,
                'status' => 'approved',
                'published_at' => now()->subDays(30),
            ],
            [
                'name' => 'Social Scheduler AI',
                'slug' => 'social-scheduler-ai',
                'description' => 'Automatically schedule social media posts at optimal times based on audience engagement analytics. Multi-platform support.',
                'category_id' => $social?->id,
                'tags' => ['scheduling', 'social', 'automation', 'optimal-timing'],
                'icon' => 'fas fa-calendar-check',
                'screenshots' => ['/images/marketplace/social-scheduler-1.png'],
                'demo_url' => 'https://demo.example.com/social-scheduler',
                'pricing_type' => 'paid',
                'pricing_config' => ['monthly_price' => 19, 'annual_price' => 190],
                'features' => ['Optimal time detection', 'Multi-platform scheduling', 'Content calendar', 'Team collaboration', 'Performance analytics'],
                'requirements' => ['social_posting feature enabled', 'at least 1 social account connected'],
                'install_count' => 890,
                'rating_avg' => 4.5,
                'rating_count' => 156,
                'is_featured' => true,
                'is_approved' => true,
                'status' => 'approved',
                'published_at' => now()->subDays(20),
            ],
            [
                'name' => 'Campaign Optimizer',
                'slug' => 'campaign-optimizer',
                'description' => 'AI-powered campaign optimization that adjusts targeting, bidding, and creative elements in real-time for maximum ROAS.',
                'category_id' => $campaigns?->id,
                'tags' => ['campaign', 'optimization', 'roas', 'targeting'],
                'icon' => 'fas fa-crosshairs',
                'screenshots' => ['/images/marketplace/campaign-opt-1.png', '/images/marketplace/campaign-opt-2.png'],
                'demo_url' => 'https://demo.example.com/campaign-optimizer',
                'pricing_type' => 'pricing_tiers',
                'pricing_config' => ['starter' => ['price' => 49, 'campaigns' => 3], 'professional' => ['price' => 149, 'campaigns' => 10]],
                'features' => ['Real-time bid adjustment', 'Creative testing', 'Audience refinement', 'ROAS tracking', 'Budget pacing'],
                'requirements' => ['campaign management feature', 'ad account connected'],
                'install_count' => 567,
                'rating_avg' => 4.3,
                'rating_count' => 89,
                'is_featured' => false,
                'is_approved' => true,
                'status' => 'approved',
                'published_at' => now()->subDays(15),
            ],
            [
                'name' => 'Analytics Insight Engine',
                'slug' => 'analytics-insight-engine',
                'description' => 'Transform raw data into actionable insights. Generates natural-language reports with recommendations for improvement.',
                'category_id' => $analytics?->id,
                'tags' => ['analytics', 'insights', 'reporting', 'ai-analysis'],
                'icon' => 'fas fa-brain',
                'screenshots' => ['/images/marketplace/analytics-1.png'],
                'demo_url' => 'https://demo.example.com/analytics-insight',
                'pricing_type' => 'free',
                'pricing_config' => null,
                'features' => ['Natural language summaries', 'Trend detection', 'Anomaly alerts', 'Custom metrics', 'Weekly digests'],
                'requirements' => ['analytics feature enabled'],
                'install_count' => 2100,
                'rating_avg' => 4.6,
                'rating_count' => 478,
                'is_featured' => true,
                'is_approved' => true,
                'status' => 'approved',
                'published_at' => now()->subDays(45),
            ],
            [
                'name' => 'SEO Keyword Genius',
                'slug' => 'seo-keyword-genius',
                'description' => 'Discover high-value keyword opportunities, track rankings, and get content recommendations based on search intent analysis.',
                'category_id' => $seo?->id,
                'tags' => ['seo', 'keywords', 'research', 'rank-tracking'],
                'icon' => 'fas fa-key',
                'screenshots' => ['/images/marketplace/seo-1.png', '/images/marketplace/seo-2.png'],
                'demo_url' => 'https://demo.example.com/seo-genius',
                'pricing_type' => 'paid',
                'pricing_config' => ['monthly_price' => 39, 'annual_price' => 390, 'trial_days' => 14],
                'features' => ['Keyword discovery', 'Rank tracking', 'SERP analysis', 'Content gap analysis', 'Competitor research'],
                'requirements' => ['seo feature enabled', 'website URL configured'],
                'install_count' => 730,
                'rating_avg' => 4.4,
                'rating_count' => 201,
                'is_featured' => false,
                'is_approved' => true,
                'status' => 'approved',
                'published_at' => now()->subDays(10),
            ],
        ];

        foreach ($agents as $agent) {
            AgentMarketplaceItem::create($agent);
        }
    }
}
