<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\Analytics\PlatformMetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchPlatformMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        private readonly SocialAccount $account,
    ) {}

    public function handle(PlatformMetricsService $metricsService): void
    {
        try {
            if ($this->account->isExpired()) {
                Log::warning('Skipping metrics fetch for expired token', [
                    'account_id' => $this->account->id,
                    'platform' => $this->account->platform,
                ]);
                return;
            }

            $metricsService->fetchAndStoreMetrics($this->account);
        } catch (\Exception $e) {
            Log::error('Failed to fetch platform metrics', [
                'account_id' => $this->account->id,
                'platform' => $this->account->platform,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function tags(): array
    {
        return [
            'platform:' . $this->account->platform,
            'account:' . $this->account->id,
            'job:metrics-sync',
        ];
    }
}
