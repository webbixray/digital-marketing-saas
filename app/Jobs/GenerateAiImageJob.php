<?php

namespace App\Jobs;

use App\Models\Agency;
use App\Models\MediaAsset;
use App\Services\Media\AiImageGenerationService;
use App\Services\AI\Gateway\Exceptions\RateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateAiImageJob implements ShouldQueue
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
        public string $style = 'photorealistic',
        public string $size = '1024x1024',
        public ?int $userId = null,
        public ?string $folder = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiImageGenerationService $service): void
    {
        try {
            $result = $service->generateImage(
                prompt: $this->prompt,
                style: $this->style,
                size: $this->size,
            );

            // Store the generated image as a MediaAsset
            if (! empty($result['image_url'])) {
                $this->storeGeneratedImage($result);
            }

            Log::info('AI image generated successfully', [
                'agency_id' => $this->agency->id,
                'prompt' => $this->prompt,
                'style' => $this->style,
                'size' => $this->size,
            ]);
        } catch (RateLimitException $e) {
            Log::warning("GenerateAiImageJob rate-limited for agency #{$this->agency->id}: {$e->getMessage()}");
            throw $e;
        } catch (\Exception $e) {
            Log::error("GenerateAiImageJob failed for agency #{$this->agency->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateAiImageJob permanently failed for agency #{$this->agency->id}", [
            'prompt' => $this->prompt,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Store the generated image as a MediaAsset.
     */
    protected function storeGeneratedImage(array $result): void
    {
        try {
            // Download image from URL and store locally
            $imageData = file_get_contents($result['image_url']);

            if ($imageData === false) {
                Log::warning("Failed to download generated image for agency #{$this->agency->id}");
                return;
            }

            $filename = 'ai-'.Str::random(12).'.jpg';
            $directory = "media/{$this->agency->id}/".($this->folder ?? 'ai-generated');
            $path = $directory.'/'.$filename;

            Storage::disk('public')->put($path, $imageData);

            MediaAsset::create([
                'agency_id' => $this->agency->id,
                'user_id' => $this->userId,
                'name' => 'AI: '.Str::limit($this->prompt, 50),
                'file_path' => $path,
                'file_type' => 'image',
                'mime_type' => 'image/jpeg',
                'file_size' => strlen($imageData),
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'folder' => $this->folder ?? 'ai-generated',
                'tags' => ['ai-generated', $result['style']],
                'is_public' => false,
                'usage_count' => 0,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to store generated image: {$e->getMessage()}");
        }
    }
}
