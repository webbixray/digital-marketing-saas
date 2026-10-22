<?php

namespace App\Jobs;

use App\Models\DataDeletionRequest;
use App\Services\GDPR\GDPRComplianceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DataDeletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $agencyId;

    public int $userId;

    public function __construct(int $agencyId, int $userId)
    {
        $this->agencyId = $agencyId;
        $this->userId = $userId;
    }

    public function handle(GDPRComplianceService $gdprService): void
    {
        try {
            $gdprService->deleteUserData($this->agencyId, $this->userId);

            Log::info('GDPR Data deletion completed', [
                'user_id' => $this->userId,
                'agency_id' => $this->agencyId,
            ]);
        } catch (\Exception $e) {
            Log::error('GDPR Data deletion failed', [
                'user_id' => $this->userId,
                'agency_id' => $this->agencyId,
                'error' => $e->getMessage(),
            ]);

            DataDeletionRequest::where('user_id', $this->userId)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            throw $e;
        }
    }
}
