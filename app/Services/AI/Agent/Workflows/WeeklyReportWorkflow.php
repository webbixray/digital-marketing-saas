<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

class WeeklyReportWorkflow implements AgentWorkflowTemplate
{
    public function getName(): string
    {
        return 'weekly_report';
    }

    public function getDescription(): string
    {
        return 'Automated weekly reporting: gather metrics, identify trends, generate insights, and create executive summary.';
    }

    public function getTasks(): array
    {
        return [
            [
                'agent' => 'analytics_agent',
                'task_type' => 'performance_analysis',
                'prompt' => 'Gather all key performance metrics for the past week across all marketing channels. Include social media engagement, email metrics, campaign performance, and website analytics.',
                'data' => ['scope' => 'weekly_all_channels', 'metrics' => ['engagement', 'reach', 'conversions', 'roi']],
                'metadata' => ['step' => 1, 'label' => 'Metrics Gathering'],
            ],
            [
                'agent' => 'analytics_agent',
                'task_type' => 'trend_detection',
                'prompt' => 'Identify key trends and patterns in the weekly data. Compare against previous weeks and highlight significant changes, growth areas, and declining metrics.',
                'data' => ['scope' => 'weekly_comparison', 'lookback_weeks' => 4],
                'metadata' => ['step' => 2, 'label' => 'Trend Detection'],
            ],
            [
                'agent' => 'analytics_agent',
                'task_type' => 'recommendation',
                'prompt' => 'Generate actionable insights and strategic recommendations based on the weekly performance data and identified trends. Prioritize high-impact opportunities.',
                'data' => ['analysis_type' => 'weekly_insights', 'priority' => 'high_impact'],
                'metadata' => ['step' => 3, 'label' => 'Insights Generation'],
            ],
            [
                'agent' => 'content_agent',
                'task_type' => 'content_generate',
                'prompt' => 'Create a comprehensive executive summary of the weekly report. Include key highlights, performance overview, top insights, and recommended actions in a professional format.',
                'data' => ['content_type' => 'weekly_report_summary', 'tone' => 'executive', 'format' => 'structured'],
                'metadata' => ['step' => 4, 'label' => 'Summary Creation'],
            ],
        ];
    }

    public function getRequiredFeatures(): array
    {
        return ['analytics', 'content_generation'];
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
