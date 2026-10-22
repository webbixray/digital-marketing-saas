<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

class ContentCalendarWorkflow implements AgentWorkflowTemplate
{
    public function getName(): string
    {
        return 'content_calendar';
    }

    public function getDescription(): string
    {
        return 'Weekly content planning: detect trending topics, generate content, schedule posts at optimal times, and suggest improvements.';
    }

    public function getTasks(): array
    {
        return [
            [
                'agent' => 'analytics_agent',
                'task_type' => 'trend_detection',
                'prompt' => 'Analyze current trending topics and hashtags relevant to the agency\'s industry. Identify the top 5 trending themes for this week.',
                'data' => ['scope' => 'weekly', 'limit' => 5],
                'metadata' => ['step' => 1, 'label' => 'Trend Detection'],
            ],
            [
                'agent' => 'content_agent',
                'task_type' => 'content_generate',
                'prompt' => 'Generate engaging social media content for each trending topic identified. Create posts optimized for engagement.',
                'data' => ['content_type' => 'social_media', 'tone' => 'professional'],
                'metadata' => ['step' => 2, 'label' => 'Content Generation'],
            ],
            [
                'agent' => 'social_media_agent',
                'task_type' => 'post_schedule',
                'prompt' => 'Schedule the generated content at optimal posting times based on audience engagement patterns.',
                'data' => ['schedule_window' => 'next_7_days', 'optimize_for' => 'engagement'],
                'metadata' => ['step' => 3, 'label' => 'Post Scheduling'],
            ],
            [
                'agent' => 'analytics_agent',
                'task_type' => 'recommendation',
                'prompt' => 'Analyze the planned content calendar and suggest improvements for better reach and engagement.',
                'data' => ['analysis_type' => 'content_calendar_optimization'],
                'metadata' => ['step' => 4, 'label' => 'Recommendations'],
            ],
        ];
    }

    public function getRequiredFeatures(): array
    {
        return ['content_generation', 'social_media', 'analytics'];
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
