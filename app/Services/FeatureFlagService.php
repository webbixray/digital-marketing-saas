<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlagService
{
    /**
     * Feature flags with their default configurations.
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
     * Check if a feature flag is enabled.
     * Uses Redis cache with tagging per agency.
     */
    public function isEnabled(?Agency $agency, string $featureCode): bool
    {
        // Check Redis for agency-specific override
        if ($agency) {
            $agencyKey = "feature_flag:{$agency->id}:{$featureCode}";
            $agencyValue = Cache::tags(["agency:{$agency->id}"])->get($agencyKey);
            if ($agencyValue !== null) {
                return (bool) $agencyValue;
            }
        }

        // Check Redis for global flag
        $globalKey = "feature_flag:{$featureCode}";
        $globalValue = Cache::tags(['global_flags'])->get($globalKey);
        if ($globalValue !== null) {
            return (bool) $globalValue;
        }

        // Fall back to default configuration
        return $this->getDefaultEnabled($agency, $featureCode);
    }

    /**
     * Enable a feature flag for an agency (or globally if agency is null).
     */
    public function enable(?Agency $agency, string $featureCode): void
    {
        $tag = $agency ? "agency:{$agency->id}" : 'global_flags';
        $key = $agency
            ? "feature_flag:{$agency->id}:{$featureCode}"
            : "feature_flag:{$featureCode}";

        Cache::tags([$tag])->put($key, true, now()->addDays(30));

        Log::info('Feature flag enabled', [
            'agency_id' => $agency?->id,
            'feature' => $featureCode,
        ]);
    }

    /**
     * Disable a feature flag for an agency (or globally if agency is null).
     */
    public function disable(?Agency $agency, string $featureCode): void
    {
        $tag = $agency ? "agency:{$agency->id}" : 'global_flags';
        $key = $agency
            ? "feature_flag:{$agency->id}:{$featureCode}"
            : "feature_flag:{$featureCode}";

        Cache::tags([$tag])->put($key, false, now()->addDays(30));

        Log::info('Feature flag disabled', [
            'agency_id' => $agency?->id,
            'feature' => $featureCode,
        ]);
    }

    /**
     * Get all feature flags with their status for an agency.
     */
    public function getAll(?Agency $agency): array
    {
        $results = [];

        foreach (array_keys(self::FEATURES) as $featureCode) {
            $results[$featureCode] = $this->isEnabled($agency, $featureCode);
        }

        return $results;
    }

    /**
     * Get default enabled status from configuration.
     */
    private function getDefaultEnabled(?Agency $agency, string $featureCode): bool
    {
        $feature = self::FEATURES[$featureCode] ?? null;

        if (! $feature) {
            Log::warning("Unknown feature flag: {$featureCode}");
            return false;
        }

        if (! $feature['enabled']) {
            return false;
        }

        if ($agency && ! in_array($agency->subscription_plan, $feature['plans'])) {
            return false;
        }

        if ($feature['rollout_percent'] < 100 && $agency) {
            return $this->isInRolloutGroup($agency->id, $featureCode, $feature['rollout_percent']);
        }

        return true;
    }

    /**
     * Check if agency is in rollout group.
     */
    private function isInRolloutGroup(int $agencyId, string $featureCode, int $rolloutPercent): bool
    {
        $hash = crc32("{$agencyId}:{$featureCode}");
        $bucket = $hash % 100;

        return $bucket < $rolloutPercent;
    }

    /**
     * Clear feature flag cache for an agency.
     */
    public function clearCache(int $agencyId): void
    {
        Cache::tags(["agency:{$agencyId}"])->flush();
    }
}
