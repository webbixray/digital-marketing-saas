<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlagService
{
    /**
     * Feature flags with their configurations.
     */
    private const FEATURES = [
        'ai_content_generation' => [
            'enabled' => true,
            'plans' => ['free', 'starter', 'pro', 'enterprise'],
            'rollout_percent' => 100,
        ],
        'advanced_analytics' => [
            'enabled' => true,
            'plans' => ['pro', 'enterprise'],
            'rollout_percent' => 100,
        ],
        'workflow_automation' => [
            'enabled' => true,
            'plans' => ['starter', 'pro', 'enterprise'],
            'rollout_percent' => 100,
        ],
        'social_listening' => [
            'enabled' => true,
            'plans' => ['pro', 'enterprise'],
            'rollout_percent' => 100,
        ],
        'white_label' => [
            'enabled' => true,
            'plans' => ['enterprise'],
            'rollout_percent' => 100,
        ],
        'api_access' => [
            'enabled' => true,
            'plans' => ['pro', 'enterprise'],
            'rollout_percent' => 100,
        ],
        'bulk_operations' => [
            'enabled' => true,
            'plans' => ['starter', 'pro', 'enterprise'],
            'rollout_percent' => 100,
        ],
        'ab_testing' => [
            'enabled' => true,
            'plans' => ['pro', 'enterprise'],
            'rollout_percent' => 100,
        ],
        'custom_reports' => [
            'enabled' => true,
            'plans' => ['enterprise'],
            'rollout_percent' => 100,
        ],
        'priority_support' => [
            'enabled' => true,
            'plans' => ['enterprise'],
            'rollout_percent' => 100,
        ],
    ];

    /**
     * Check if a feature is enabled for an agency.
     */
    public function isEnabled(Agency $agency, string $featureCode): bool
    {
        $feature = self::FEATURES[$featureCode] ?? null;

        if (!$feature) {
            Log::warning("Unknown feature flag: {$featureCode}");
            return false;
        }

        // Check if feature is globally enabled
        if (!$feature['enabled']) {
            return false;
        }

        // Check if agency's plan has access
        if (!in_array($agency->subscription_plan, $feature['plans'])) {
            return false;
        }

        // Check rollout percentage
        if ($feature['rollout_percent'] < 100) {
            return $this->isInRolloutGroup($agency->id, $featureCode, $feature['rollout_percent']);
        }

        // Check for agency-specific override
        $override = $this->getAgencyOverride($agency->id, $featureCode);
        if ($override !== null) {
            return $override;
        }

        return true;
    }

    /**
     * Get all features available for an agency.
     */
    public function getAvailableFeatures(Agency $agency): array
    {
        $available = [];

        foreach (self::FEATURES as $code => $config) {
            if ($this->isEnabled($agency, $code)) {
                $available[$code] = [
                    'enabled' => true,
                    'rollout_percent' => $config['rollout_percent'],
                ];
            }
        }

        return $available;
    }

    /**
     * Get all features with their status for an agency.
     */
    public function getAllFeatures(Agency $agency): array
    {
        $features = [];

        foreach (self::FEATURES as $code => $config) {
            $features[$code] = [
                'enabled' => $this->isEnabled($agency, $code),
                'plan_access' => in_array($agency->subscription_plan, $config['plans']),
                'rollout_percent' => $config['rollout_percent'],
                'required_plans' => $config['plans'],
            ];
        }

        return $features;
    }

    /**
     * Set agency-specific feature override.
     */
    public function setAgencyOverride(int $agencyId, string $featureCode, bool $enabled): void
    {
        $key = "feature_override:{$agencyId}:{$featureCode}";
        Cache::put($key, $enabled, now()->addDays(30));

        Log::info("Feature override set", [
            'agency_id' => $agencyId,
            'feature' => $featureCode,
            'enabled' => $enabled,
        ]);
    }

    /**
     * Remove agency-specific feature override.
     */
    public function removeAgencyOverride(int $agencyId, string $featureCode): void
    {
        $key = "feature_override:{$agencyId}:{$featureCode}";
        Cache::forget($key);
    }

    /**
     * Check if agency is in rollout group.
     */
    private function isInRolloutGroup(int $agencyId, string $featureCode, int $rolloutPercent): bool
    {
        // Use consistent hashing to determine if agency is in rollout group
        $hash = crc32("{$agencyId}:{$featureCode}");
        $bucket = $hash % 100;

        return $bucket < $rolloutPercent;
    }

    /**
     * Get agency-specific override.
     */
    private function getAgencyOverride(int $agencyId, string $featureCode): ?bool
    {
        $key = "feature_override:{$agencyId}:{$featureCode}";
        $override = Cache::get($key);

        return $override !== null ? (bool) $override : null;
    }

    /**
     * Clear feature flag cache for an agency.
     */
    public function clearCache(int $agencyId): void
    {
        foreach (array_keys(self::FEATURES) as $featureCode) {
            $key = "feature_override:{$agencyId}:{$featureCode}";
            Cache::forget($key);
        }
    }
}
