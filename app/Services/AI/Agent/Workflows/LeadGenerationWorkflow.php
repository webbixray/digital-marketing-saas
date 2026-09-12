<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

class LeadGenerationWorkflow implements AgentWorkflowTemplate
{
    public function getName(): string
    {
        return 'lead_generation';
    }

    public function getDescription(): string
    {
        return 'Lead generation workflow: identify target audience, create lead magnets, optimize landing pages, and suggest improvements.';
    }

    public function getTasks(): array
    {
        return [
            [
                'agent' => 'campaign_agent',
                'task_type' => 'audience_suggest',
                'prompt' => 'Identify the ideal target audience for lead generation campaigns. Define buyer personas, demographics, interests, and pain points.',
                'data' => ['audience_type' => 'lead_gen', 'platforms' => ['facebook', 'instagram', 'google', 'linkedin']],
                'metadata' => ['step' => 1, 'label' => 'Target Audience'],
            ],
            [
                'agent' => 'content_agent',
                'task_type' => 'content_generate',
                'prompt' => 'Create compelling lead magnets including ebooks, checklists, webinars, and free tools. Generate landing page copy and email sequences for lead nurturing.',
                'data' => ['content_type' => 'lead_magnets', 'formats' => ['ebook', 'checklist', 'webinar', 'tool']],
                'metadata' => ['step' => 2, 'label' => 'Lead Magnets'],
            ],
            [
                'agent' => 'campaign_agent',
                'task_type' => 'campaign_optimize',
                'prompt' => 'Optimize landing pages for maximum conversion. Suggest improvements to headlines, CTAs, form fields, page layout, and trust signals.',
                'data' => ['optimization_target' => 'conversion_rate', 'max_changes_per_campaign' => 5],
                'metadata' => ['step' => 3, 'label' => 'Landing Page Optimization'],
            ],
            [
                'agent' => 'analytics_agent',
                'task_type' => 'recommendation',
                'prompt' => 'Analyze the lead generation funnel and suggest improvements for each stage. Recommend strategies to increase lead quality and conversion rates.',
                'data' => ['analysis_type' => 'lead_gen_funnel_optimization'],
                'metadata' => ['step' => 4, 'label' => 'Improvement Suggestions'],
            ],
        ];
    }

    public function getRequiredFeatures(): array
    {
        return ['campaigns', 'content_generation', 'analytics'];
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
