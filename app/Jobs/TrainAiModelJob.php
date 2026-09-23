<?php

namespace App\Jobs;

use App\Models\AiTrainingJob;
use App\Services\AI\Training\TrainingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrainAiModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600;

    /**
     * The number of unhandled jobs to allow before firing the failed event.
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $jobId,
    ) {
        $this->onQueue('ai-training');
    }

    /**
     * Execute the job.
     */
    public function handle(TrainingService $trainingService): void
    {
        Log::info("TrainAiModelJob: starting training for job #{$this->jobId}");

        try {
            $job = AiTrainingJob::find($this->jobId);

            if (! $job) {
                Log::error("TrainAiModelJob: training job #{$this->jobId} not found.");
                return;
            }

            if (! in_array($job->status, ['queued', 'running'], true)) {
                Log::warning("TrainAiModelJob: job #{$this->jobId} has status '{$job->status}', skipping.");
                return;
            }

            $trainingService->trainModel($this->jobId);

            Log::info("TrainAiModelJob: completed training for job #{$this->jobId}");
        } catch (\Exception $e) {
            Log::error("TrainAiModelJob: failed for job #{$this->jobId}: {$e->getMessage()}");

            // Update job status on failure
            AiTrainingJob::where('id', $this->jobId)->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("TrainAiModelJob: permanently failed for job #{$this->jobId}: {$exception->getMessage()}");

        AiTrainingJob::where('id', $this->jobId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
