<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeDunningService;
use App\Services\Email\MailDeliverabilityService;
use App\Services\Queue\QueueHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __construct(
        private readonly MailDeliverabilityService $mailService,
        private readonly QueueHealthService $queueService,
        private readonly StripeDunningService $stripeService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'mail' => $this->mailService->getHealthCheck(),
            'queue' => $this->queueService->getHealthCheck(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = collect($checks)->every(fn ($check) => $check['healthy'] ?? false);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('services.sentry.release', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn ($check) => $check['healthy'] ?? false);

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

            return ['healthy' => true, 'message' => 'Database connected'];
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

            return ['healthy' => $value === true, 'message' => 'Cache operational'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = storage_path('framework/health');
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $file = $path.'/check.txt';
            file_put_contents($file, 'ok');
            $value = file_get_contents($file);
            unlink($file);
            rmdir($path);

            return ['healthy' => $value === 'ok', 'message' => 'Storage writable'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }
}
