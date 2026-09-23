<?php

namespace App\Jobs;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Models\User;
use App\Notifications\TranslationCompleteNotification;
use App\Services\AI\ContentTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * The number of unhandled jobs to allow before firing the failed event.
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Agency $agency,
        public array $items,
        public string $sourceLang,
        public string $targetLang,
        public int $userId,
    ) {
        $this->onQueue('translations');
    }

    /**
     * Execute the job.
     */
    public function handle(ContentTranslationService $translationService): void
    {
        $results = [];
        $errors = [];
        $totalCost = 0;
        $totalTokens = 0;

        try {
            $batchResult = $translationService->translateBatch(
                items: $this->items,
                sourceLang: $this->sourceLang,
                targetLang: $this->targetLang,
                agency: $this->agency,
            );

            $results = $batchResult['results'];
            $errors = $batchResult['errors'];

            // Calculate totals
            foreach ($results as $result) {
                $totalCost += $result['cost_usd'] ?? 0;
                $totalTokens += $result['tokens_used'] ?? 0;
            }

            // Notify user of completion
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new TranslationCompleteNotification(
                    totalItems: count($this->items),
                    successCount: count($results),
                    errorCount: count($errors),
                    targetLang: $this->targetLang,
                    totalCost: $totalCost,
                ));
            }

            Log::info("Batch translation completed for agency #{$this->agency->id}", [
                'total' => count($this->items),
                'success' => count($results),
                'errors' => count($errors),
                'cost' => $totalCost,
            ]);
        } catch (\Exception $e) {
            Log::error("TranslateContentJob failed for agency #{$this->agency->id}: {$e->getMessage()}");

            // Notify user of failure
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new TranslationCompleteNotification(
                    totalItems: count($this->items),
                    successCount: 0,
                    errorCount: count($this->items),
                    targetLang: $this->targetLang,
                    totalCost: 0,
                    failed: true,
                    errorMessage: $e->getMessage(),
                ));
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("TranslateContentJob permanently failed for agency #{$this->agency->id}: {$exception->getMessage()}");

        // Notify user of permanent failure
        try {
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new TranslationCompleteNotification(
                    totalItems: count($this->items),
                    successCount: 0,
                    errorCount: count($this->items),
                    targetLang: $this->targetLang,
                    totalCost: 0,
                    failed: true,
                    errorMessage: 'Translation job permanently failed after multiple retries.',
                ));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send translation failure notification: {$e->getMessage()}");
        }
    }
}
