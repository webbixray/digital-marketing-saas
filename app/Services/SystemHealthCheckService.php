<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Centralized system health check service.
 * Consolidates duplicated health check logic from AdminDashboardController and HealthCheckController.
 */
class SystemHealthCheckService
{
    public function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $time = round((microtime(true) - $start) * 1000, 2);

            return [
                'healthy' => true,
                'status' => 'ok',
                'message' => 'Database connected',
                'response_time_ms' => $time,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            return [
                'healthy' => true,
                'status' => 'ok',
                'message' => 'Queue operational',
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            $healthy = $value === true;

            return [
                'healthy' => $healthy,
                'status' => $healthy ? 'ok' : 'warning',
                'message' => $healthy ? 'Cache operational' : 'Cache read/write issue',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function checkStorage(): array
    {
        try {
            $path = storage_path('framework/health');
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $file = $path . '/check.txt';
            file_put_contents($file, 'ok');
            $value = file_get_contents($file);
            unlink($file);
            rmdir($path);

            $healthy = $value === 'ok';

            return [
                'healthy' => $healthy,
                'status' => $healthy ? 'ok' : 'warning',
                'message' => $healthy ? 'Storage writable' : 'Storage read/write issue',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run all core health checks.
     *
     * @return array{status: string, checks: array, healthy: bool}
     */
    public function runAll(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = collect($checks)->every(fn ($check) => $check['healthy'] ?? false);

        return [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'healthy' => $healthy,
            'checks' => $checks,
        ];
    }
}
