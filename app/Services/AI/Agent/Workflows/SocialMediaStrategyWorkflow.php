<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

class SocialMediaStrategyWorkflow implements AgentWorkflowTemplate
{
    public function getName(): string
    {
        return 'social_media_strategy';
    }

    public function getDescription(): string
    {
        return 'Social media strategy workflow: analyze competitors, analyze own engagement, generate content calendar, and schedule posts.';
    }

    public function getTasks(): array
    {
        return [
            [
                'agent' => 'analytics_agent',
                'task_type' => 'competitor_analysis',
                'prompt' => 'Analyze the social media presence of key competitors. Identify their content strategies, posting frequency, engagement rates, and top-performing content themes.',
                'data' => ['analysis_depth' => 'comprehensive', 'competitor_count' => 5],
                'metadata' => ['step' => 1, 'label' => 'Competitor Analysis'],
            ],
            [
                'agent' => 'social_media_agent',
                'task_type' => 'engagement_analysis',
                'prompt' => 'Analyze our own social media engagement data. Identify top-performing content, optimal posting times, audience demographics, and engagement trends.',
                'data' => ['date_range' => '30 days', 'platforms' => ['instagram', 'facebook', 'twitter', 'linkedin']],
                'metadata' => ['step' => 2, 'label' => 'Engagement Analysis'],
            ],
            [
                'agent' => 'content_agent',
                'task_type' => 'content_generate',
                'prompt' => 'Generate a comprehensive content calendar for the next 2 weeks based on competitor insights and engagement analysis. Include post ideas, captions, and visual suggestions.',
                'data' => ['content_type' => 'content_calendar', 'duration' => '14_days', 'tone' => 'professional'],
                'metadata' => ['step' => 3, 'label' => 'Content Calendar'],
            ],
            [
                'agent' => 'social_media_agent',
                'task_type' => 'post_schedule',
                'prompt' => 'Schedule the content calendar posts at optimal times for each platform based on audience engagement patterns and platform algorithms.',
                'data' => ['schedule_window' => 'next_14_days', 'optimize_for' => 'engagement'],
                'metadata' => ['step' => 4, 'label' => 'Post Scheduling'],
            ],
        ];
    }

    public function getRequiredFeatures(): array
    {
        return ['social_media', 'content_generation', 'analytics'];
    }

    public function isAvailable(Agency $agency): bool
    {
        foreach ($this->getRequiredFeatures() as $feature) {
            if (! $agency->isFeatureAvailable($feature)) {
                return false;
            }
        }

        return true;
    }
}
