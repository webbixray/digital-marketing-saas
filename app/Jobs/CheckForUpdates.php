<?php

namespace App\Jobs;

use App\Services\VersionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckForUpdates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function handle(VersionService $versionService): void
    {
        $latest = $versionService->getLatestRelease();

        if ($versionService->isUpdateAvailable($latest['version'] ?? '')) {
            info('Update available: '.$latest['version']);
        }
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(6);
    }
}
