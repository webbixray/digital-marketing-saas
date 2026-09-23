<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MediaAnalyticsService
{
    /**
     * Get most used assets by agency.
     */
    public function getMostUsedAssets(int $agencyId, int $limit = 10): Collection
    {
        return MediaAsset::where('agency_id', $agencyId)
            ->orderByDesc('usage_count')
            ->orderByDesc('last_used_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get storage usage trends over time.
     */
    public function getStorageTrends(int $agencyId, int $days = 30): array
    {
        $trends = MediaAsset::where('agency_id', $agencyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(file_size) as total_size, COUNT(*) as upload_count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $result = [];
        $runningTotal = 0;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $record = $trends->firstWhere('date', $date);
            $runningTotal += $record ? $record->total_size : 0;

            $result[] = [
                'date' => $date,
                'size_bytes' => $runningTotal,
                'daily_uploaded' => $record ? $record->total_size : 0,
                'uploads' => $record ? $record->upload_count : 0,
            ];
        }

        return $result;
    }

    /**
     * Get file type breakdown by agency.
     */
    public function getFileTypeBreakdown(int $agencyId): array
    {
        $breakdown = MediaAsset::where('agency_id', $agencyId)
            ->selectRaw('file_type, COUNT(*) as count, SUM(file_size) as total_size')
            ->groupBy('file_type')
            ->get();

        $totalSize = $breakdown->sum('total_size');
        $result = [];

        foreach ($breakdown as $item) {
            $result[] = [
                'file_type' => $item->file_type,
                'count' => $item->count,
                'total_size' => $item->total_size,
                'percentage' => $totalSize > 0 ? round(($item->total_size / $totalSize) * 100, 1) : 0,
                'human_size' => $this->formatBytes($item->total_size),
            ];
        }

        return $result;
    }

    /**
     * Get upload activity over time.
     */
    public function getUploadActivity(int $agencyId, int $days = 30): array
    {
        $activity = MediaAsset::where('agency_id', $agencyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as upload_count, COUNT(DISTINCT user_id) as unique_users')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $record = $activity->firstWhere('date', $date);

            $result[] = [
                'date' => $date,
                'uploads' => $record ? $record->upload_count : 0,
                'unique_users' => $record ? $record->unique_users : 0,
            ];
        }

        return $result;
    }

    /**
     * Get usage statistics for a specific asset.
     */
    public function getAssetUsageStats(int $assetId): array
    {
        $asset = MediaAsset::findOrFail($assetId);

        // Get posts that reference this asset
        $posts = SocialPost::where('agency_id', $asset->agency_id)
            ->where(function ($query) use ($asset) {
                $query->whereJsonContains('media', $asset->id)
                    ->orWhere('media', 'like', '%'.$asset->file_path.'%');
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'platform', 'status', 'created_at']);

        return [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'usage_count' => $asset->usage_count,
            'last_used_at' => $asset->last_used_at?->toISOString(),
            'total_posts' => $posts->count(),
            'posts_by_platform' => $posts->groupBy('platform')->map->count(),
            'recent_posts' => $posts->map(fn ($post) => [
                'id' => $post->id,
                'platform' => $post->platform,
                'status' => $post->status,
                'created_at' => $post->created_at->toISOString(),
            ]),
        ];
    }

    /**
     * Get summary analytics for dashboard.
     */
    public function getSummaryAnalytics(int $agencyId): array
    {
        $totalAssets = MediaAsset::where('agency_id', $agencyId)->count();
        $totalSize = MediaAsset::where('agency_id', $agencyId)->sum('file_size');
        $totalUsage = MediaAsset::where('agency_id', $agencyId)->sum('usage_count');

        $todayUploads = MediaAsset::where('agency_id', $agencyId)
            ->whereDate('created_at', today())
            ->count();

        $monthUploads = MediaAsset::where('agency_id', $agencyId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total_assets' => $totalAssets,
            'total_storage_used' => $totalSize,
            'total_storage_human' => $this->formatBytes($totalSize),
            'total_usage_count' => $totalUsage,
            'uploads_today' => $todayUploads,
            'uploads_this_month' => $monthUploads,
        ];
    }

    /**
     * Format bytes to human-readable format.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
