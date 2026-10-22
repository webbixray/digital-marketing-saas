<?php

namespace App\Jobs\AI;

use App\Models\Agency;
use App\Services\AI\AiContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public Agency $agency,
        public string $prompt,
        public string $contentType = 'post',
        public ?string $systemPrompt = null,
        public string $model = 'gpt-4o',
    ) {}

    public function handle(AiContentService $service): void
    {
        $service->generate(
            agency: $this->agency,
            prompt: $this->prompt,
            contentType: $this->contentType,
            systemPrompt: $this->systemPrompt,
            model: $this->model,
        );
    }
}
