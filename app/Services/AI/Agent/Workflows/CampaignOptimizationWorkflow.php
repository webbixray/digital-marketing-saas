<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

class CampaignOptimizationWorkflow implements AgentWorkflowTemplate
{
    public function getName(): string
    {
        return 'campaign_optimization';
    }

    public function getDescription(): string
    {
        return 'Campaign auto-optimization: analyze current campaigns, suggest optimizations, reallocate budget, and optimize ad creatives.';
    }

    public function getTasks(): array
    {
        return [
            [
                'agent' => 'analytics_agent',
                'task_type' => 'performance_analysis',
                'prompt' => 'Analyze the performance of all active campaigns. Identify underperforming campaigns, high-performing segments, and key metrics trends.',
                'data' => ['scope' => 'all_active_campaigns', 'metrics' => ['ctr', 'cpc', 'roas', 'conversions']],
                'metadata' => ['step' => 1, 'label' => 'Performance Analysis'],
            ],
            [
                'agent' => 'campaign_agent',
                'task_type' => 'campaign_optimize',
                'prompt' => 'Based on the performance analysis, suggest specific optimizations for each campaign including targeting, bidding, and creative adjustments.',
                'data' => ['optimization_target' => 'roas', 'max_changes_per_campaign' => 3],
                'metadata' => ['step' => 2, 'label' => 'Campaign Optimization'],
            ],
            [
                'agent' => 'campaign_agent',
                'task_type' => 'budget_allocate',
                'prompt' => 'Reallocate budget across campaigns to maximize overall ROI. Shift budget from underperforming to high-performing campaigns.',
                'data' => ['reallocation_strategy' => 'performance_based', 'max_shift_percent' => 30],
                'metadata' => ['step' => 3, 'label' => 'Budget Reallocation'],
            ],
            [
                'agent' => 'content_agent',
                'task_type' => 'content_optimize',
                'prompt' => 'Optimize ad creatives for underperforming campaigns. Suggest improvements to headlines, descriptions, and calls-to-action.',
                'data' => ['focus' => 'underperforming_creatives', 'output_format' => 'actionable_suggestions'],
                'metadata' => ['step' => 4, 'label' => 'Creative Optimization'],
            ],
        ];
    }

    public function getRequiredFeatures(): array
    {
        return ['campaigns', 'analytics', 'content_generation'];
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
