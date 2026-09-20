<?php

namespace App\Services\AI;

use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;

class SentimentAnalysisService
{
    protected AiGateway $gateway;

    public function __construct(AiGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Analyze sentiment of given text.
     * Returns: 'positive', 'negative', or 'neutral'
     */
    public function analyze(string $text): string
    {
        try {
            if (empty(trim($text))) {
                return 'neutral';
            }

            $prompt = <<<PROMPT
Classify the following text's sentiment as exactly one word: positive, negative, or neutral.
Only respond with the classification word, nothing else.

Text: "{$text}"
PROMPT;

            $request = AiRequest::text(
                prompt: $prompt,
                systemPrompt: 'You are a sentiment analysis classifier. Respond with only: positive, negative, or neutral.',
                model: 'gpt-4o-mini',
                task: 'fast'
            );

            $agency = auth()->user()->agency ?? null;
            if (!$agency) {
                return 'neutral';
            }

            $response = $this->gateway->send($request, $agency);

            $result = strtolower(trim($response->getContent()));

            if (in_array($result, ['positive', 'negative', 'neutral'])) {
                return $result;
            }

            // Try to extract sentiment from longer response
            if (str_contains($result, 'positive')) {
                return 'positive';
            } elseif (str_contains($result, 'negative')) {
                return 'negative';
            }

            return 'neutral';
        } catch (\Exception $e) {
            Log::warning("AI sentiment analysis failed: {$e->getMessage()}");
            return 'neutral';
        }
    }

    /**
     * Batch analyze sentiment for multiple texts.
     */
    public function analyzeBatch(array $texts): array
    {
        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->analyze($text);
        }

        return $results;
    }
}
