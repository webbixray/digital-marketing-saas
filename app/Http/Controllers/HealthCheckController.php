<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn($check) => $check['healthy'] ?? false);

        return response()->json([
            'status' => $healthy ? 'ready' : 'not_ready',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'alive']);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'version' => config('services.sentry.release', '1.0.0'),
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function diskSpace(): JsonResponse
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $usedPercent = $total > 0 ? round((1 - $free / $total) * 100, 2) : 0;

        return response()->json([
            'status' => 'ok',
            'free_bytes' => $free,
            'total_bytes' => $total,
            'used_percent' => $usedPercent,
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['healthy' => true];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            return ['healthy' => $value === true];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }
}
