<?php

namespace App\Jobs;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Services\AI\AiContentService;
use App\Services\AI\Gateway\Exceptions\RateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAiContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of unhandled jobs to allow before firing the failed event.
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Agency $agency,
        public string $prompt,
        public string $contentType = 'post',
        public ?string $systemPrompt = null,
        public string $model = 'gpt-4o',
        public string $task = 'fast',
        public float $temperature = 0.7,
        public int $maxTokens = 2048,
        public ?int $logId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiContentService $service): void
    {
        try {
            $response = $service->generate(
                agency: $this->agency,
                prompt: $this->prompt,
                contentType: $this->contentType,
                systemPrompt: $this->systemPrompt,
                model: $this->model,
                task: $this->task,
                temperature: $this->temperature,
                maxTokens: $this->maxTokens,
            );

            $this->storeResult($response);
        } catch (RateLimitException $e) {
            Log::warning("GenerateAiContentJob rate-limited for agency #{$this->agency->id}: {$e->getMessage()}");
            throw $e;
        } catch (\Exception $e) {
            Log::error("GenerateAiContentJob failed for agency #{$this->agency->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Update the content log record if one was created
        if ($this->logId) {
            AiContentLog::where('id', $this->logId)->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }

        Log::error("GenerateAiContentJob permanently failed for agency #{$this->agency->id}: {$exception->getMessage()}");
    }

    /**
     * Store the AI generation result in the log.
     */
    protected function storeResult($response): void
    {
        if ($this->logId) {
            AiContentLog::where('id', $this->logId)->update([
                'status' => 'completed',
                'response' => $response->content,
                'provider' => $response->provider,
                'model' => $response->model,
                'total_tokens' => $response->totalTokens,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
                'cost_usd' => $response->costUsd,
            ]);
        }
    }
}
