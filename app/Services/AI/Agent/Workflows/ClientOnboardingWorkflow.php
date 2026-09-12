<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

class ClientOnboardingWorkflow implements AgentWorkflowTemplate
{
    public function getName(): string
    {
        return 'client_onboarding';
    }

    public function getDescription(): string
    {
        return 'New client onboarding: send welcome message, generate onboarding content, analyze competitors, and suggest target audience.';
    }

    public function getTasks(): array
    {
        return [
            [
                'agent' => 'support_agent',
                'task_type' => 'response_suggest',
                'prompt' => 'Generate a personalized welcome message for the new client. Include an introduction to the agency\'s services and next steps.',
                'data' => ['message_type' => 'welcome', 'tone' => 'friendly_professional'],
                'metadata' => ['step' => 1, 'label' => 'Welcome Message'],
            ],
            [
                'agent' => 'content_agent',
                'task_type' => 'content_generate',
                'prompt' => 'Generate onboarding content for the new client including a getting-started guide, service overview, and FAQ document.',
                'data' => ['content_type' => 'onboarding', 'format' => 'multi_document'],
                'metadata' => ['step' => 2, 'label' => 'Onboarding Content'],
            ],
            [
                'agent' => 'analytics_agent',
                'task_type' => 'competitor_analysis',
                'prompt' => 'Analyze the new client\'s industry and identify their top competitors. Provide insights on competitor strategies and market positioning.',
                'data' => ['analysis_depth' => 'standard', 'competitor_count' => 5],
                'metadata' => ['step' => 3, 'label' => 'Competitor Analysis'],
            ],
            [
                'agent' => 'campaign_agent',
                'task_type' => 'audience_suggest',
                'prompt' => 'Based on the client\'s business and industry, suggest target audience segments for initial marketing campaigns.',
                'data' => ['audience_type' => 'lookalike_and_interest', 'platforms' => ['facebook', 'instagram', 'google']],
                'metadata' => ['step' => 4, 'label' => 'Target Audience'],
            ],
        ];
    }

    public function getRequiredFeatures(): array
    {
        return ['clients', 'content_generation', 'analytics', 'campaigns'];
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
