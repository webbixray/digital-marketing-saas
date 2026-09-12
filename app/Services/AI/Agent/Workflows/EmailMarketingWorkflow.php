<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

class EmailMarketingWorkflow implements AgentWorkflowTemplate
{
    public function getName(): string
    {
        return 'email_marketing';
    }

    public function getDescription(): string
    {
        return 'Email campaign workflow: analyze past email campaigns, generate subject lines and body content, segment audience, and suggest optimal send times.';
    }

    public function getTasks(): array
    {
        return [
            [
                'agent' => 'analytics_agent',
                'task_type' => 'performance_analysis',
                'prompt' => 'Analyze past email campaign performance metrics including open rates, click-through rates, conversion rates, and unsubscribe rates. Identify patterns in successful campaigns.',
                'data' => ['scope' => 'email_campaigns', 'metrics' => ['open_rate', 'click_rate', 'conversion_rate', 'unsubscribe_rate']],
                'metadata' => ['step' => 1, 'label' => 'Performance Analysis'],
            ],
            [
                'agent' => 'content_agent',
                'task_type' => 'content_generate',
                'prompt' => 'Generate compelling email subject lines and body content based on the performance analysis. Create multiple variants for A/B testing.',
                'data' => ['content_type' => 'email', 'tone' => 'professional', 'variants' => 3],
                'metadata' => ['step' => 2, 'label' => 'Content Generation'],
            ],
            [
                'agent' => 'campaign_agent',
                'task_type' => 'audience_suggest',
                'prompt' => 'Segment the email audience based on engagement history, demographics, and behavior patterns. Suggest targeted segments for personalized campaigns.',
                'data' => ['audience_type' => 'email_segments', 'platforms' => ['email']],
                'metadata' => ['step' => 3, 'label' => 'Audience Segmentation'],
            ],
            [
                'agent' => 'analytics_agent',
                'task_type' => 'recommendation',
                'prompt' => 'Suggest optimal send times for email campaigns based on audience engagement patterns, time zones, and historical open rate data.',
                'data' => ['analysis_type' => 'email_send_time_optimization'],
                'metadata' => ['step' => 4, 'label' => 'Send Time Recommendations'],
            ],
        ];
    }

    public function getRequiredFeatures(): array
    {
        return ['email_campaigns', 'content_generation', 'analytics'];
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
