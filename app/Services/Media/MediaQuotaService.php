<?php

namespace App\Services\Media;

use App\Models\Agency;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Cache;

class MediaQuotaService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get storage quota information for an agency.
     *
     * @return array{plan_limit_bytes: int|null, used_bytes: int, remaining_bytes: int|null, used_percentage: float}
     */
    public function getQuota(int $agencyId): array
    {
        return Cache::remember(
            "media:quota:{$agencyId}",
            self::CACHE_TTL,
            fn () => $this->calculateQuota($agencyId)
        );
    }

    /**
     * Check if an agency can upload a file of given size.
     */
    public function canUpload(int $agencyId, int $fileSize): bool
    {
        $quota = $this->getQuota($agencyId);

        if ($quota['remaining_bytes'] === null) {
            return true;
        }

        return $fileSize <= $quota['remaining_bytes'];
    }

    /**
     * Increment storage usage by bytes.
     */
    public function incrementUsage(int $agencyId, int $bytes): void
    {
        $this->clearCache($agencyId);
    }

    /**
     * Decrement storage usage by bytes.
     */
    public function decrementUsage(int $agencyId, int $bytes): void
    {
        $this->clearCache($agencyId);
    }

    /**
     * Get storage breakdown by file type.
     *
     * @return array<int, array{file_type: string, count: int, total_bytes: int}>
     */
    public function getStorageBreakdown(int $agencyId): array
    {
        $cacheKey = "media:breakdown:{$agencyId}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($agencyId) {
                return MediaAsset::where('agency_id', $agencyId)
                    ->selectRaw('file_type, COUNT(*) as count, COALESCE(SUM(file_size), 0) as total_bytes')
                    ->groupBy('file_type')
                    ->get()
                    ->toArray();
            }
        );
    }

    /**
     * Calculate quota from scratch.
     */
    private function calculateQuota(int $agencyId): array
    {
        $agency = Agency::findOrFail($agencyId);
        $plan = $agency->getPlanConfig();
        $planLimitBytes = $plan['storage_limit_bytes'] ?? null;
        $usedBytes = (int) MediaAsset::where('agency_id', $agencyId)->sum('file_size');

        $remainingBytes = null;
        $usedPercentage = 0.0;

        if ($planLimitBytes !== null) {
            $remainingBytes = max(0, $planLimitBytes - $usedBytes);
            $usedPercentage = $planLimitBytes > 0 ? round(($usedBytes / $planLimitBytes) * 100, 2) : 0.0;
        }

        return [
            'plan_limit_bytes' => $planLimitBytes,
            'used_bytes' => $usedBytes,
            'remaining_bytes' => $remainingBytes,
            'used_percentage' => $usedPercentage,
        ];
    }

    /**
     * Clear cached quota data for an agency.
     */
    public function clearCache(int $agencyId): void
    {
        Cache::forget("media:quota:{$agencyId}");
        Cache::forget("media:breakdown:{$agencyId}");
    }
}
