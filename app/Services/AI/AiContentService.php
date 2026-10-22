<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use Illuminate\Support\Facades\Log;

class AiContentService
{
    public function __construct(
        protected AiGateway $gateway,
    ) {}

    /**
     * Generate content using AI.
     */
    public function generate(
        Agency $agency,
        string $prompt,
        ?string $contentType = 'post',
        ?string $systemPrompt = null,
        string $model = 'gpt-4o',
        string $task = 'fast',
        float $temperature = 0.7,
        int $maxTokens = 2048,
    ): AiResponse {
        $request = new AiRequest(
            prompt: $prompt,
            systemPrompt: $systemPrompt,
            model: $model,
            temperature: $temperature,
            maxTokens: $maxTokens,
            task: $task,
            contentType: $contentType,
            action: 'generate',
        );

        try {
            $response = $this->gateway->send($request, $agency);

            // Record usage
            $this->recordUsage(
                agency: $agency,
                provider: $response->provider,
                model: $response->model,
                action: 'generate',
                contentType: $contentType,
                prompt: $prompt,
                response: $response->content,
                totalTokens: $response->totalTokens,
                promptTokens: $response->promptTokens,
                completionTokens: $response->completionTokens,
                costUsd: $response->costUsd ?? 0,
                status: 'success',
            );

            return $response;
        } catch (\Exception $e) {
            $this->recordUsage(
                agency: $agency,
                provider: 'unknown',
                model: $model,
                action: 'generate',
                contentType: $contentType,
                prompt: $prompt,
                response: '',
                totalTokens: 0,
                promptTokens: 0,
                completionTokens: 0,
                costUsd: 0,
                status: 'failed',
                errorMessage: $e->getMessage(),
            );

            throw $e;
        }
    }

    /**
     * Rewrite existing content.
     */
    public function rewrite(
        Agency $agency,
        string $content,
        ?string $instructions = null,
        string $model = 'gpt-4o',
    ): string {
        $systemPrompt = 'You are an expert marketing copywriter. Rewrite the following content to improve it.';
        if ($instructions) {
            $systemPrompt .= "\n\nInstructions: {$instructions}";
        }

        $prompt = "Content to rewrite:\n\n{$content}";

        $response = $this->generate(
            agency: $agency,
            prompt: $prompt,
            contentType: 'post',
            systemPrompt: $systemPrompt,
            model: $model,
            task: 'creative',
        );

        return $response->content;
    }

    /**
     * Generate hashtags for a topic or content.
     */
    public function generateHashtags(
        Agency $agency,
        string $topic,
        int $count = 10,
        ?string $platform = null,
    ): array {
        $platformHint = $platform ? " for {$platform}" : '';

        $prompt = "Generate {$count} relevant, high-performing hashtags{$platformHint} for: {$topic}."
            ."\n\nReturn ONLY a comma-separated list of hashtags (with # prefix). No extra text.";

        $response = $this->generate(
            agency: $agency,
            prompt: $prompt,
            contentType: 'hashtag',
            systemPrompt: 'You are a social media strategist specializing in hashtag optimization.',
            task: 'fast',
            maxTokens: 500,
        );

        return $this->parseHashtags($response->content);
    }

    /**
     * Generate content ideas.
     */
    public function generateIdeas(
        Agency $agency,
        string $topic,
        int $count = 5,
        ?string $platform = null,
    ): array {
        $platformHint = $platform ? " optimized for {$platform}" : '';

        $prompt = "Generate {$count} creative content ideas{$platformHint} about: {$topic}."
            ."\n\nFor each idea, provide:\n- Title (catchy headline)\n- Format (post, video, carousel, story, reel)\n- Brief description (2-3 sentences)\n- Target emotion/call-to-action"
            ."\n\nReturn as a JSON array of objects with keys: title, format, description, cta";

        $response = $this->generate(
            agency: $agency,
            prompt: $prompt,
            contentType: 'post',
            systemPrompt: 'You are a creative marketing strategist who generates viral content ideas.',
            task: 'creative',
            maxTokens: 2000,
        );

        return $this->parseJsonResponse($response->content);
    }

    /**
     * Summarize content.
     */
    public function summarize(
        Agency $agency,
        string $content,
        int $maxLength = 200,
    ): string {
        $prompt = "Summarize the following content in {$maxLength} words or less:\n\n{$content}";

        $response = $this->generate(
            agency: $agency,
            prompt: $prompt,
            contentType: 'post',
            systemPrompt: 'You are an expert at distilling complex content into concise, impactful summaries.',
            task: 'analysis',
            maxTokens: 1000,
        );

        return $response->content;
    }

    /**
     * Translate content to another language.
     */
    public function translate(
        Agency $agency,
        string $content,
        string $targetLanguage,
    ): string {
        $prompt = "Translate the following content to {$targetLanguage}:\n\n{$content}"
            ."\n\nIMPORTANT: Return ONLY the translated text. No explanations, no notes.";

        $response = $this->generate(
            agency: $agency,
            prompt: $prompt,
            contentType: 'post',
            systemPrompt: 'You are a professional translator with expertise in marketing content.',
            task: 'fast',
            maxTokens: 2000,
        );

        return $response->content;
    }

    /**
     * Parse hashtags from AI response.
     */
    protected function parseHashtags(string $content): array
    {
        $content = trim($content);
        $hashtags = [];

        // Try comma-separated first
        if (str_contains($content, ',')) {
            $parts = explode(',', $content);
            foreach ($parts as $part) {
                $tag = trim($part);
                if (! str_starts_with($tag, '#')) {
                    $tag = '#'.$tag;
                }
                if (strlen($tag) > 1) {
                    $hashtags[] = $tag;
                }
            }
        } else {
            // Try line-by-line
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $tag = trim($line);
                if (! str_starts_with($tag, '#')) {
                    $tag = '#'.$tag;
                }
                if (strlen($tag) > 1) {
                    $hashtags[] = $tag;
                }
            }
        }

        return array_values(array_unique($hashtags));
    }

    /**
     * Parse JSON response from AI.
     */
    protected function parseJsonResponse(string $content): array
    {
        // Try to extract JSON if wrapped in markdown
        if (preg_match('/```json\s*(.+?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/```\s*(.+?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Record AI usage to the database.
     */
    protected function recordUsage(
        Agency $agency,
        string $provider,
        string $model,
        string $action,
        ?string $contentType,
        string $prompt,
        string $response,
        int $totalTokens,
        int $promptTokens,
        int $completionTokens,
        float $costUsd,
        string $status,
        ?string $errorMessage = null,
    ): void {
        try {
            AiContentLog::create([
                'agency_id' => $agency->id,
                'provider' => $provider,
                'model' => $model,
                'action' => $action,
                'content_type' => $contentType,
                'prompt' => $prompt,
                'response' => $response,
                'total_tokens' => $totalTokens,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'cost_usd' => $costUsd,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to record AI usage: {$e->getMessage()}");
        }
    }
}
