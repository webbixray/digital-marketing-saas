<?php

namespace App\Services\Billing;

use App\Models\UsageQuota;
use Illuminate\Support\Facades\Log;

class UsageQuotaService
{
    public function getQuota(int $agencyId, string $metric): ?UsageQuota
    {
        return UsageQuota::byAgency($agencyId)->byMetric($metric)->first();
    }

    public function setQuota(int $agencyId, string $metric, int $limit, string $period = 'monthly', $resetAt = null): UsageQuota
    {
        $quota = UsageQuota::updateOrCreate(
            [
                'agency_id' => $agencyId,
                'metric' => $metric,
            ],
            [
                'limit' => $limit,
                'used' => 0,
                'period' => $period,
                'reset_at' => $resetAt ?? now()->addMonth()->startOfMonth(),
            ]
        );

        Log::info('Quota set', [
            'agency_id' => $agencyId,
            'metric' => $metric,
            'limit' => $limit,
            'period' => $period,
        ]);

        return $quota;
    }

    public function checkQuota(int $agencyId, string $metric): array
    {
        $quota = $this->getQuota($agencyId, $metric);

        if (! $quota) {
            return [
                'metric' => $metric,
                'has_quota' => false,
                'limit' => PHP_INT_MAX,
                'used' => 0,
                'remaining' => PHP_INT_MAX,
                'exceeded' => false,
                'usage_percent' => 0,
            ];
        }

        $usagePercent = $quota->limit > 0 ? round(($quota->used / $quota->limit) * 100, 2) : 0;

        return [
            'metric' => $metric,
            'has_quota' => true,
            'limit' => $quota->limit,
            'used' => $quota->used,
            'remaining' => max(0, $quota->limit - $quota->used),
            'exceeded' => $quota->used > $quota->limit,
            'usage_percent' => $usagePercent,
            'period' => $quota->period,
        ];
    }

    public function incrementUsage(int $agencyId, string $metric, int $amount): bool
    {
        $quota = UsageQuota::byAgency($agencyId)->byMetric($metric)->first();

        if (! $quota) {
            // No quota set - allow usage without tracking
            return true;
        }

        $quota->increment('used', $amount);

        Log::info('Quota usage incremented', [
            'agency_id' => $agencyId,
            'metric' => $metric,
            'amount' => $amount,
            'new_used' => $quota->fresh()->used,
            'limit' => $quota->limit,
        ]);

        return true;
    }

    public function getExceededQuotas(int $agencyId): \Illuminate\Database\Eloquent\Collection
    {
        return UsageQuota::byAgency($agencyId)
            ->whereColumn('used', '>', 'limit')
            ->where('limit', '>', 0)
            ->get();
    }

    public function getQuotaSummary(int $agencyId): array
    {
        $quotas = UsageQuota::byAgency($agencyId)->get();

        $summary = [];
        foreach ($quotas as $quota) {
            $usagePercent = $quota->limit > 0 ? round(($quota->used / $quota->limit) * 100, 2) : 0;

            $summary[] = [
                'metric' => $quota->metric,
                'limit' => $quota->limit,
                'used' => $quota->used,
                'remaining' => max(0, $quota->limit - $quota->used),
                'usage_percent' => $usagePercent,
                'exceeded' => $quota->used > $quota->limit,
                'period' => $quota->period,
            ];
        }

        return [
            'agency_id' => $agencyId,
            'quotas' => $summary,
            'total_quotas' => $quotas->count(),
            'exceeded_count' => $quotas->where('used', '>', 'limit')->count(),
            'healthy_count' => $quotas->where('used', '<=', 'limit')->count(),
        ];
    }
}
