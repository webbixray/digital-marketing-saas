<?php

namespace App\Services;

use App\Models\Agency;

class QuotaService
{
    private const CACHE_TTL = 600; // 10 minutes

    public function __construct() {}

    public function remainingPosts(Agency $agency): int
    {
        $plan = $agency->getPlanConfig();
        $limit = $plan['posts_per_month'] ?? 0;

        if ($limit === -1) {
            return -1; // unlimited
        }

        return max(0, $limit - (int) $agency->posts_count);
    }

    public function remainingAiGenerations(Agency $agency): int
    {
        $plan = $agency->getPlanConfig();
        $limit = $plan['ai_generations_per_month'] ?? 0;

        if ($limit === -1) {
            return -1; // unlimited
        }

        return max(0, $limit - (int) $agency->ai_generations_count);
    }

    public function remainingAiRequests(Agency $agency): int
    {
        $plan = $agency->getPlanConfig();
        $limit = $plan['ai_requests_per_month'] ?? 0;

        if ($limit === -1) {
            return -1;
        }

        return max(0, $limit - (int) $agency->ai_requests_count);
    }

    public function remainingSocialAccounts(Agency $agency): int
    {
        $plan = $agency->getPlanConfig();
        $limit = $plan['social_accounts'] ?? 0;

        if ($limit === -1) {
            return -1;
        }

        return max(0, $limit - (int) $agency->social_accounts_count);
    }

    public function remainingCampaigns(Agency $agency): int
    {
        $plan = $agency->getPlanConfig();
        $limit = $plan['campaigns'] ?? 0;

        if ($limit === -1) {
            return -1;
        }

        return max(0, $limit - (int) $agency->campaigns_count);
    }

    public function remainingClients(Agency $agency): int
    {
        $plan = $agency->getPlanConfig();
        $limit = $plan['clients'] ?? 0;

        if ($limit === -1) {
            return -1;
        }

        return max(0, $limit - (int) $agency->clients_count);
    }

    public function isOverQuota(Agency $agency, string $feature): bool
    {
        return match ($feature) {
            'posts' => $this->remainingPosts($agency) !== -1 && $this->remainingPosts($agency) <= 0,
            'ai_generations' => $this->remainingAiGenerations($agency) !== -1 && $this->remainingAiGenerations($agency) <= 0,
            'ai_requests' => $this->remainingAiRequests($agency) !== -1 && $this->remainingAiRequests($agency) <= 0,
            'social_accounts' => $this->remainingSocialAccounts($agency) !== -1 && $this->remainingSocialAccounts($agency) <= 0,
            'campaigns' => $this->remainingCampaigns($agency) !== -1 && $this->remainingCampaigns($agency) <= 0,
            'clients' => $this->remainingClients($agency) !== -1 && $this->remainingClients($agency) <= 0,
            default => false,
        };
    }

    public function usagePercentage(Agency $agency, string $feature): float
    {
        $plan = $agency->getPlanConfig();
        $limit = match ($feature) {
            'posts' => $plan['posts_per_month'] ?? 0,
            'ai_generations' => $plan['ai_generations_per_month'] ?? 0,
            'ai_requests' => $plan['ai_requests_per_month'] ?? 0,
            'social_accounts' => $plan['social_accounts'] ?? 0,
            'campaigns' => $plan['campaigns'] ?? 0,
            'clients' => $plan['clients'] ?? 0,
            default => 0,
        };

        if ($limit === -1 || $limit === 0) {
            return 0.0;
        }

        $used = match ($feature) {
            'posts' => (int) $agency->posts_count,
            'ai_generations' => (int) $agency->ai_generations_count,
            'ai_requests' => (int) $agency->ai_requests_count,
            'social_accounts' => (int) $agency->social_accounts_count,
            'campaigns' => (int) $agency->campaigns_count,
            'clients' => (int) $agency->clients_count,
            default => 0,
        };

        return round(min(100, ($used / $limit) * 100), 1);
    }

    public function getLimit(Agency $agency, string $feature): int
    {
        return match ($feature) {
            'posts' => $agency->getPlanConfig()['posts_per_month'] ?? 0,
            'ai_generations' => $agency->getPlanConfig()['ai_generations_per_month'] ?? 0,
            'ai_requests' => $agency->getPlanConfig()['ai_requests_per_month'] ?? 0,
            'social_accounts' => $agency->getPlanConfig()['social_accounts'] ?? 0,
            'campaigns' => $agency->getPlanConfig()['campaigns'] ?? 0,
            'clients' => $agency->getPlanConfig()['clients'] ?? 0,
            'users' => $agency->getPlanConfig()['users'] ?? 0,
            'invoices' => $agency->getPlanConfig()['invoices'] ?? -1,
            'landing_pages' => $agency->getPlanConfig()['landing_pages'] ?? -1,
            'forms' => $agency->getPlanConfig()['forms'] ?? -1,
            default => 0,
        };
    }

    public function getPercentage(Agency $agency, string $feature, int $used): float
    {
        $limit = $this->getLimit($agency, $feature);
        if ($limit === -1 || $limit === 0) {
            return 0.0;
        }

        return round(min(100, ($used / $limit) * 100), 1);
    }

    public function incrementPostCount(Agency $agency): void
    {
        $agency->increment('posts_count');
    }

    public function incrementAiRequestCount(Agency $agency): void
    {
        $agency->increment('ai_requests_count');
    }

    public function incrementAiGenerationCount(Agency $agency): void
    {
        $agency->increment('ai_generations_count');
    }

    public function incrementSocialAccountCount(Agency $agency): void
    {
        $agency->increment('social_accounts_count');
    }

    public function incrementCampaignCount(Agency $agency): void
    {
        $agency->increment('campaigns_count');
    }

    public function incrementClientCount(Agency $agency): void
    {
        $agency->increment('clients_count');
    }

    public function decrementPostCount(Agency $agency): void
    {
        $agency->decrement('posts_count');
    }

    public function decrementAiRequestCount(Agency $agency): void
    {
        $agency->decrement('ai_requests_count');
    }

    public function decrementAiGenerationCount(Agency $agency): void
    {
        $agency->decrement('ai_generations_count');
    }

    public function decrementSocialAccountCount(Agency $agency): void
    {
        $agency->decrement('social_accounts_count');
    }

    public function canAddSocialAccount(Agency $agency): bool
    {
        return $this->remainingSocialAccounts($agency) > 0;
    }

    public function canPublishPost(Agency $agency): bool
    {
        return $this->remainingPosts($agency) !== 0;
    }

    public function canGenerateAiContent(Agency $agency): bool
    {
        return $this->remainingAiGenerations($agency) > 0;
    }

    public function getQuotaStatus(Agency $agency): array
    {
        return [
            'posts' => [
                'used' => (int) $agency->posts_count,
                'limit' => $agency->getPlanConfig()['posts_per_month'] ?? 0,
                'remaining' => $this->remainingPosts($agency),
                'percentage' => $this->usagePercentage($agency, 'posts'),
            ],
            'ai_generations' => [
                'used' => (int) $agency->ai_generations_count,
                'limit' => $agency->getPlanConfig()['ai_generations_per_month'] ?? 0,
                'remaining' => $this->remainingAiGenerations($agency),
                'percentage' => $this->usagePercentage($agency, 'ai_generations'),
            ],
            'ai_credits' => [
                'used' => (int) $agency->ai_credits_purchased - (int) $agency->ai_credits,
                'limit' => (int) $agency->ai_credits_purchased,
                'remaining' => (int) $agency->ai_credits,
                'percentage' => $agency->ai_credits_purchased > 0
                    ? round((($agency->ai_credits_purchased - $agency->ai_credits) / $agency->ai_credits_purchased) * 100, 1)
                    : 0,
            ],
            'social_accounts' => [
                'used' => (int) $agency->social_accounts_count,
                'limit' => $agency->getPlanConfig()['social_accounts'] ?? 0,
                'remaining' => $this->remainingSocialAccounts($agency),
                'percentage' => $this->usagePercentage($agency, 'social_accounts'),
            ],
            'campaigns' => [
                'used' => (int) $agency->campaigns_count,
                'limit' => $agency->getPlanConfig()['campaigns'] ?? 0,
                'remaining' => $this->remainingCampaigns($agency),
                'percentage' => $this->usagePercentage($agency, 'campaigns'),
            ],
            'clients' => [
                'used' => (int) $agency->clients_count,
                'limit' => $agency->getPlanConfig()['clients'] ?? 0,
                'remaining' => $this->remainingClients($agency),
                'percentage' => $this->usagePercentage($agency, 'clients'),
            ],
        ];
    }

    /**
     * Check if agency has AI credits available
     */
    public function hasAiCredits(Agency $agency): bool
    {
        return $agency->ai_credits > 0;
    }

    /**
     * Deduct AI credit from agency
     */
    public function deductAiCredit(Agency $agency): bool
    {
        if ($agency->ai_credits <= 0) {
            return false;
        }

        $agency->decrement('ai_credits');

        return true;
    }

    /**
     * Add AI credits to agency
     */
    public function addAiCredits(Agency $agency, int $credits): void
    {
        $agency->increment('ai_credits', $credits);
        $agency->increment('ai_credits_purchased', $credits);
    }
}
