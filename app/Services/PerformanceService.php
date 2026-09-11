<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PerformanceService
{
    /**
     * Get database performance metrics.
     */
    public function getDatabaseMetrics(): array
    {
        $connection = config('database.default');
        
        if ($connection === 'sqlite') {
            $size = filesize(database_path('database.sqlite'));
            return [
                'driver' => 'sqlite',
                'size_bytes' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
            ];
        }

        $tables = DB::select('SHOW TABLE STATUS');
        $totalSize = 0;
        $tableCount = 0;

        foreach ($tables as $table) {
            $totalSize += ($table->Data_length ?? 0) + ($table->Index_length ?? 0);
            $tableCount++;
        }

        return [
            'driver' => $connection,
            'tables' => $tableCount,
            'size_bytes' => $totalSize,
            'size_mb' => round($totalSize / 1024 / 1024, 2),
        ];
    }

    /**
     * Get cache performance metrics.
     */
    public function getCacheMetrics(): array
    {
        return [
            'driver' => config('cache.default', 'file'),
            'prefix' => config('cache.prefix'),
        ];
    }

    /**
     * Get storage metrics.
     */
    public function getStorageMetrics(): array
    {
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;

        return [
            'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
            'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'usage_percent' => round(($usedSpace / $totalSpace) * 100, 1),
        ];
    }

    /**
     * Get queue metrics.
     */
    public function getQueueMetrics(): array
    {
        return [
            'driver' => config('queue.default'),
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];
    }

    /**
     * Get all performance metrics.
     */
    public function getAllMetrics(): array
    {
        return [
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'storage' => $this->getStorageMetrics(),
            'queue' => $this->getQueueMetrics(),
            'memory' => [
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
            ],
        ];
    }
}
