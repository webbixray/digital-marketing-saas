<?php

namespace App\Http\Controllers;

use App\Services\AI\Gateway\AiGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    public function __construct(
        private readonly AiGateway $aiGateway,
    ) {}

    /**
     * Minimum free disk space in bytes before warning (500 MB).
     */
    private const MIN_DISK_SPACE = 500 * 1024 * 1024;

    /**
     * Maximum queue size before warning.
     */
    private const MAX_QUEUE_SIZE = 1000;

    /**
     * Basic health check - always returns 200 if the app is running.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'DigitalMarketingSaaS',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Readiness check - verifies all dependencies are working.
     */
    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'ai_gateway' => $this->checkAiGateway(),
            'disk_space' => $this->checkDiskSpace(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ready' : 'not_ready',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    /**
     * Liveness check - verifies the application is alive.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detailed system status for monitoring.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'queue' => $this->checkQueue(),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health_check', true, 10);

            return Cache::get('health_check') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            return Storage::disk('local')->put('health_check.txt', 'ok');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkAiGateway(): bool
    {
        try {
            $gateway = $this->aiGateway;

            // If no providers are registered, AI is not configured — that's
            // a valid state, not a failure. Only fail if providers exist but
            // none are reachable.
            if ($gateway->getProviders()->isEmpty()) {
                return true;
            }

            return $gateway->hasAvailableProvider();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check disk space availability.
     * Returns true if free space is above the minimum threshold.
     */
    private function checkDiskSpace(): bool
    {
        try {
            $freeSpace = disk_free_space(storage_path());

            return $freeSpace !== false && $freeSpace >= self::MIN_DISK_SPACE;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check queue status.
     * Returns true if the queue is responding and size is within limits.
     */
    private function checkQueue(): bool
    {
        try {
            $queueDriver = config('queue.default');

            // For sync driver, queue is always "healthy"
            if ($queueDriver === 'sync') {
                return true;
            }

            // For database queue, check table exists and count pending jobs
            if ($queueDriver === 'database') {
                $pending = DB::table('jobs')->count();

                return $pending < self::MAX_QUEUE_SIZE;
            }

            // For Redis queue, check connection and size
            if ($queueDriver === 'redis') {
                $connection = config('queue.connections.redis.connection', 'default');
                $size = Queue::size();

                return $size < self::MAX_QUEUE_SIZE;
            }

            // For other drivers, try a simple connection test
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Disk space information endpoint for detailed monitoring.
     */
    public function diskSpace(): JsonResponse
    {
        $paths = [
            'storage' => storage_path(),
            'base' => base_path(),
            'public' => public_path(),
        ];

        $diskInfo = [];

        foreach ($paths as $name => $path) {
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);

            $diskInfo[$name] = [
                'path' => $path,
                'free_bytes' => $freeSpace,
                'total_bytes' => $totalSpace,
                'used_bytes' => $totalSpace !== false && $freeSpace !== false ? $totalSpace - $freeSpace : 0,
                'free_human' => $freeSpace !== false ? $this->formatBytes($freeSpace) : 'unknown',
                'total_human' => $totalSpace !== false ? $this->formatBytes($totalSpace) : 'unknown',
                'usage_percent' => $totalSpace !== false && $freeSpace !== false
                    ? round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2)
                    : 0,
                'healthy' => $freeSpace !== false && $freeSpace >= self::MIN_DISK_SPACE,
            ];
        }

        $allHealthy = ! in_array(false, array_column($diskInfo, 'healthy'), true);

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'warning',
            'disks' => $diskInfo,
            'threshold_bytes' => self::MIN_DISK_SPACE,
            'threshold_human' => $this->formatBytes(self::MIN_DISK_SPACE),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Queue status endpoint for monitoring queue health.
     */
    public function queueStatus(): JsonResponse
    {
        $queueDriver = config('queue.default');
        $defaultQueue = config('queue.connections.'.$queueDriver.'.queue', 'default');

        $status = [
            'driver' => $queueDriver,
            'default_queue' => $defaultQueue,
            'timestamp' => now()->toIso8601String(),
        ];

        $failedJobsTableExists = Schema::hasTable('failed_jobs');

        if ($queueDriver === 'database') {
            $status['pending'] = DB::table('jobs')->count();
            $status['failed'] = $failedJobsTableExists ? DB::table('failed_jobs')->count() : 0;
            $status['pending_by_queue'] = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->pluck('count', 'queue')
                ->toArray();
        } elseif ($queueDriver === 'redis') {
            $status['pending'] = Queue::size();
            $status['failed'] = $failedJobsTableExists ? DB::table('failed_jobs')->count() : 0;
        } else {
            $status['pending'] = 'n/a';
            $status['failed'] = $failedJobsTableExists ? DB::table('failed_jobs')->count() : 0;
        }

        $status['healthy'] = $this->checkQueue();
        $status['max_queue_size'] = self::MAX_QUEUE_SIZE;

        return response()->json($status);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }
}
