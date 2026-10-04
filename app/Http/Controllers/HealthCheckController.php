<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeDunningService;
use App\Services\Email\MailDeliverabilityService;
use App\Services\Queue\QueueHealthService;
use App\Services\SystemHealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    public function __construct(
        private readonly MailDeliverabilityService $mailService,
        private readonly QueueHealthService $queueService,
        private readonly StripeDunningService $stripeService,
        private readonly SystemHealthCheckService $systemHealth,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function check(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->systemHealth->checkDatabase(),
            'cache' => $this->systemHealth->checkCache(),
            'mail' => $this->mailService->getHealthCheck(),
            'queue' => $this->queueService->getHealthCheck(),
            'storage' => $this->systemHealth->checkStorage(),
        ];

        // Core dependencies must be up for the service to be healthy.
        $coreUp = collect($checks)->only(['database', 'cache', 'storage'])
            ->every(fn ($check) => $check['healthy'] ?? false);

        // Mail/queue driver choice (log mailer, sync queue) is a configuration
        // advisory — common and intentional in local/dev — not an outage.
        // In non-production environments these never degrade the reported status.
        $advisoryRelevant = ! app()->environment('testing', 'local');

        $advisories = $advisoryRelevant
            ? collect($checks)->only(['mail', 'queue'])
                ->filter(fn ($check) => ! ($check['healthy'] ?? true))
                ->map(fn ($check, $name) => $check['issues'] ?? ["{$name} not configured for production"])
                ->flatten()
                ->values()
                ->all()
            : [];

        $status = $coreUp ? (empty($advisories) ? 'ok' : 'degraded') : 'unhealthy';

        // Detailed dependency checks (driver names, connection internals, disk
        // paths, error strings) are an information-disclosure vector. They are
        // returned only to an authenticated owner/admin; anonymous callers — e.g.
        // uptime monitors and load balancers — get the overall status only.
        $user = $request->user();
        $maySeeDetails = $user !== null && in_array($user->role ?? '', ['owner', 'admin'], true);

        $payload = [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($maySeeDetails) {
            $payload['version'] = config('services.sentry.release', '1.0.0');
            $payload['environment'] = app()->environment();
            $payload['checks'] = $checks;
            $payload['advisories'] = $advisories;
        }

        return response()->json($payload, $coreUp ? 200 : 503);
    }

    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->systemHealth->checkDatabase(),
            'cache' => $this->systemHealth->checkCache(),
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
}
