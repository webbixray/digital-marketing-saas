<?php

namespace App\Services;

/**
 * Central registry for agent metadata - descriptions, categories, and labels.
 * Consolidates duplicated match expressions from AgentController and AgentDashboardController.
 */
class AgentRegistry
{
    private const DESCRIPTIONS = [
        'content_agent' => 'Creates and optimizes content for social media, blogs, and marketing campaigns.',
        'analytics_agent' => 'Analyzes performance data, detects trends, and provides strategic recommendations.',
        'security_agent' => 'Performs security audits and vulnerability scans to protect agency assets.',
        'campaign_agent' => 'Optimizes marketing campaigns, allocates budgets, and designs A/B tests.',
        'social_media_agent' => 'Manages post scheduling, engagement analysis, and reply suggestions.',
        'social_agent' => 'Manages social media posting and engagement.',
        'support_agent' => 'Classifies support tickets, suggests responses, and detects escalations.',
    ];

    private const CATEGORIES = [
        'content_agent' => 'Marketing',
        'campaign_agent' => 'Marketing',
        'social_media_agent' => 'Marketing',
        'analytics_agent' => 'Analytics',
        'security_agent' => 'Security',
        'support_agent' => 'Support',
        'social_agent' => 'Social',
    ];

    private const DEFAULT_DESCRIPTION = 'An intelligent AI agent handling specialized tasks.';
    private const DEFAULT_CATEGORY = 'General';

    public static function getDescription(string $name): string
    {
        return self::DESCRIPTIONS[$name] ?? self::DEFAULT_DESCRIPTION;
    }

    public static function getCategory(string $name): string
    {
        return self::CATEGORIES[$name] ?? self::DEFAULT_CATEGORY;
    }
}
