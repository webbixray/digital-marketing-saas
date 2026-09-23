<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use Illuminate\Support\Facades\Log;

class ContentTranslationService
{
    /**
     * Supported languages for translation.
     */
    private const SUPPORTED_LANGUAGES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'nl' => 'Dutch',
        'ru' => 'Russian',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'tr' => 'Turkish',
        'pl' => 'Polish',
        'sv' => 'Swedish',
        'da' => 'Danish',
        'fi' => 'Finnish',
        'no' => 'Norwegian',
        'th' => 'Thai',
        'vi' => 'Vietnamese',
        'id' => 'Indonesian',
        'ms' => 'Malay',
    ];

    /**
     * Rate limit: max translations per minute per agency.
     */
    private const RATE_LIMIT_PER_MINUTE = 20;

    /**
     * Cache for rate limiting.
     */
    private static array $rateCache = [];

    public function __construct(
        protected AiGateway $gateway,
    ) {}

    /**
     * Translate content preserving formatting.
     */
    public function translateContent(
        string $content,
        string $sourceLang,
        string $targetLang,
        string $context = 'general',
        ?Agency $agency = null,
    ): array {
        if (! $this->isSupportedLanguage($sourceLang)) {
            throw new \InvalidArgumentException("Unsupported source language: {$sourceLang}");
        }

        if (! $this->isSupportedLanguage($targetLang)) {
            throw new \InvalidArgumentException("Unsupported target language: {$targetLang}");
        }

        if ($sourceLang === $targetLang) {
            return [
                'translated' => $content,
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'quality_score' => 100.0,
                'cost_usd' => 0,
                'tokens_used' => 0,
            ];
        }

        $preservedContent = $this->preserveFormatting($content);

        $systemPrompt = $this->buildTranslationSystemPrompt($sourceLang, $targetLang, $context);
        $prompt = "Translate the following content:\n\n{$preservedContent}";

        try {
            $request = new AiRequest(
                prompt: $prompt,
                systemPrompt: $systemPrompt,
                model: 'gpt-4o-mini',
                temperature: 0.3,
                maxTokens: 4096,
                task: 'fast',
                contentType: 'translation',
                action: 'translate',
            );

            $response = $agency
                ? $this->gateway->send($request, $agency)
                : $this->sendWithMockProvider($request);

            $translated = $this->restoreFormatting($response->content);

            $qualityScore = $this->getTranslationQuality($content, $translated, $sourceLang, $targetLang);

            if ($agency) {
                $this->recordUsage(
                    agency: $agency,
                    provider: $response->provider,
                    model: $response->model,
                    sourceLang: $sourceLang,
                    targetLang: $targetLang,
                    originalLength: strlen($content),
                    translatedLength: strlen($translated),
                    tokens: $response->totalTokens,
                    costUsd: $response->costUsd ?? 0,
                );
            }

            return [
                'translated' => $translated,
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'quality_score' => $qualityScore,
                'cost_usd' => $response->costUsd ?? 0,
                'tokens_used' => $response->totalTokens,
            ];
        } catch (\Exception $e) {
            Log::error("Translation failed: {$e->getMessage()}", [
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
            ]);
            throw $e;
        }
    }

    /**
     * Translate social post content with hashtags.
     */
    public function translateSocialPost(array $post, string $targetLang, ?Agency $agency = null): array
    {
        $sourceLang = $post['detected_lang'] ?? $post['source_lang'] ?? 'en';
        $content = $post['content'] ?? '';
        $hashtags = $post['hashtags'] ?? [];

        // Translate content
        $contentResult = $this->translateContent($content, $sourceLang, $targetLang, 'social_post', $agency);

        // Translate hashtags (extract, translate, re-prefix)
        $translatedHashtags = [];
        if (! empty($hashtags)) {
            $translatedHashtags = $this->translateHashtags($hashtags, $sourceLang, $targetLang);
        }

        return [
            'content' => $contentResult['translated'],
            'hashtags' => $translatedHashtags,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'quality_score' => $contentResult['quality_score'],
            'cost_usd' => $contentResult['cost_usd'],
            'tokens_used' => $contentResult['tokens_used'],
        ];
    }

    /**
     * Translate email subject and body.
     */
    public function translateEmail(string $subject, string $body, string $targetLang, ?Agency $agency = null): array
    {
        $sourceLang = 'en'; // Default source for email, could be detected

        $subjectResult = $this->translateContent($subject, $sourceLang, $targetLang, 'email_subject', $agency);
        $bodyResult = $this->translateContent($body, $sourceLang, $targetLang, 'email_body', $agency);

        return [
            'subject' => $subjectResult['translated'],
            'body' => $bodyResult['translated'],
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'quality_score' => ($subjectResult['quality_score'] + $bodyResult['quality_score']) / 2,
            'cost_usd' => $subjectResult['cost_usd'] + $bodyResult['cost_usd'],
            'tokens_used' => $subjectResult['tokens_used'] + $bodyResult['tokens_used'],
        ];
    }

    /**
     * Batch translation with rate limiting.
     */
    public function translateBatch(array $items, string $sourceLang, string $targetLang, ?Agency $agency = null): array
    {
        $results = [];
        $errors = [];
        $rateKey = $agency ? "agency:{$agency->id}" : 'guest';

        foreach ($items as $index => $item) {
            // Rate limiting check
            if ($this->isRateLimited($rateKey)) {
                $errors[] = [
                    'index' => $index,
                    'error' => 'Rate limit exceeded. Please try again in a moment.',
                ];
                continue;
            }

            try {
                $content = is_array($item) ? ($item['content'] ?? '') : $item;
                $context = is_array($item) ? ($item['context'] ?? 'general') : 'general';

                $result = $this->translateContent($content, $sourceLang, $targetLang, $context, $agency);
                $result['index'] = $index;
                $results[] = $result;

                $this->recordRateLimit($rateKey);
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'total_processed' => count($results),
            'total_errors' => count($errors),
        ];
    }

    /**
     * Get translation quality score.
     */
    public function getTranslationQuality(string $original, string $translated, string $sourceLang, string $targetLang): float
    {
        // Basic heuristic quality scoring
        $score = 100.0;

        // Check length ratio (translations should be within 50%-200% of original)
        $originalLength = strlen($original);
        $translatedLength = strlen($translated);

        if ($originalLength > 0) {
            $lengthRatio = $translatedLength / $originalLength;

            if ($lengthRatio < 0.3 || $ratio > 3.0) {
                $score -= 30;
            } elseif ($lengthRatio < 0.5 || $ratio > 2.0) {
                $score -= 15;
            }
        }

        // Check for identical content (likely failed translation)
        if (strtolower(trim($original)) === strtolower(trim($translated))) {
            $score -= 50;
        }

        // Check word count ratio
        $originalWords = str_word_count($original);
        $translatedWords = str_word_count($translated);

        if ($originalWords > 0) {
            $wordRatio = $translatedWords / $originalWords;

            if ($wordRatio < 0.3 || $wordRatio > 3.0) {
                $score -= 20;
            } elseif ($wordRatio < 0.5 || $wordRatio > 2.0) {
                $score -= 10;
            }
        }

        return max(0.0, min(100.0, round($score, 1)));
    }

    /**
     * Preserve HTML/markdown formatting during translation.
     */
    public function preserveFormatting(string $content): string
    {
        // Store HTML tags for restoration
        $placeholders = [];

        // Replace HTML tags with placeholders
        $content = preg_replace_callback(
            '/<[^>]+>/',
            function ($matches) use (&$placeholders) {
                $key = '%%HTML_TAG_' . count($placeholders) . '%%';
                $placeholders[$key] = $matches[0];

                return $key;
            },
            $content,
        );

        // Replace markdown headers
        $content = preg_replace_callback(
            '/^(#{1,6}\s+.+)$/m',
            function ($matches) use (&$placeholders) {
                $key = '%%MD_HEADER_' . count($placeholders) . '%%';
                $placeholders[$key] = $matches[0];

                return $key;
            },
            $content,
        );

        // Replace markdown bold/italic
        $content = preg_replace_callback(
            '/(\*\*.*?\*\*|\*.*?\*|__.*?__|_.*?_)/',
            function ($matches) use (&$placeholders) {
                $key = '%%MD_FMT_' . count($placeholders) . '%%';
                $placeholders[$key] = $matches[0];

                return $key;
            },
            $content,
        );

        // Store placeholders for later restoration
        $this->formattingPlaceholders = $placeholders;

        return $content;
    }

    /**
     * Restore formatting placeholders to original values.
     */
    protected function restoreFormatting(string $content): string
    {
        $placeholders = $this->formattingPlaceholders ?? [];
        $this->formattingPlaceholders = [];

        foreach ($placeholders as $key => $original) {
            $content = str_replace($key, $original, $content);
        }

        return $content;
    }

    /**
     * Formatting placeholders storage.
     */
    protected array $formattingPlaceholders = [];

    /**
     * Get supported languages.
     */
    public static function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Check if a language is supported.
     */
    public function isSupportedLanguage(string $lang): bool
    {
        return array_key_exists($lang, self::SUPPORTED_LANGUAGES);
    }

    /**
     * Detect language from text content.
     */
    public function detectLanguage(string $content, ?Agency $agency = null): array
    {
        $prompt = "Detect the language of the following text. Return ONLY the ISO 639-1 language code (e.g., 'en', 'es', 'fr'). If uncertain, return 'unknown'.\n\nText: " . substr($content, 0, 500);

        try {
            $request = new AiRequest(
                prompt: $prompt,
                systemPrompt: 'You are a language detection expert.',
                model: 'gpt-4o-mini',
                temperature: 0.0,
                maxTokens: 10,
                task: 'fast',
                action: 'detect_language',
            );

            $response = $agency
                ? $this->gateway->send($request, $agency)
                : $this->sendWithMockProvider($request);

            $detectedCode = strtolower(trim($response->content));
            // Clean up response - extract just the language code
            preg_match('/^[a-z]{2}$/', $detectedCode, $matches);
            $detectedCode = $matches[0] ?? 'unknown';

            $languageName = self::SUPPORTED_LANGUAGES[$detectedCode] ?? 'Unknown';

            return [
                'code' => $detectedCode,
                'name' => $languageName,
                'confidence' => $detectedCode !== 'unknown' ? 'high' : 'low',
            ];
        } catch (\Exception $e) {
            Log::warning("Language detection failed: {$e->getMessage()}");

            return [
                'code' => 'unknown',
                'name' => 'Unknown',
                'confidence' => 'none',
            ];
        }
    }

    /**
     * Translate hashtags.
     */
    protected function translateHashtags(array $hashtags, string $sourceLang, string $targetLang): array
    {
        $translated = [];

        foreach ($hashtags as $tag) {
            // Remove # prefix
            $tagText = ltrim($tag, '#');

            try {
                $result = $this->translateContent($tagText, $sourceLang, $targetLang, 'general');
                $translatedTag = '#' . ltrim($result['translated'], '#');
                $translated[] = $translatedTag;
            } catch (\Exception $e) {
                // Keep original on failure
                $translated[] = $tag;
            }
        }

        return $translated;
    }

    /**
     * Build system prompt for translation.
     */
    protected function buildTranslationSystemPrompt(string $sourceLang, string $targetLang, string $context): string
    {
        $sourceName = self::SUPPORTED_LANGUAGES[$sourceLang] ?? $sourceLang;
        $targetName = self::SUPPORTED_LANGUAGES[$targetLang] ?? $targetLang;

        $contextPrompts = [
            'general' => 'You are a professional translator. Maintain the original meaning, tone, and style.',
            'social_post' => 'You are a social media translation expert. Keep the casual tone, translate hashtags appropriately, and maintain engagement.',
            'email_subject' => 'You are an email marketing translator. Keep subject lines compelling, concise, and actionable.',
            'email_body' => 'You are an email marketing translator. Maintain professional tone, formatting, and call-to-action effectiveness.',
        ];

        $contextPrompt = $contextPrompts[$context] ?? $contextPrompts['general'];

        return "{$contextPrompt}\n\n"
            . "Translate from {$sourceName} to {$targetName}.\n"
            . "IMPORTANT:\n"
            . "- Preserve all formatting, line breaks, and structure\n"
            . "- Keep proper nouns, brand names, and URLs unchanged\n"
            . "- Maintain the original tone and intent\n"
            . "- Return ONLY the translated text, no explanations or notes.";
    }

    /**
     * Send with mock provider for testing/non-agency contexts.
     */
    protected function sendWithMockProvider(AiRequest $request): AiResponse
    {
        // Simple mock for contexts without agency
        return new AiResponse(
            content: "[Translated] " . substr($request->prompt, 0, 200),
            model: 'mock',
            provider: 'mock',
            promptTokens: 100,
            completionTokens: 100,
            totalTokens: 200,
            costUsd: 0.001,
        );
    }

    /**
     * Check rate limit.
     */
    protected function isRateLimited(string $key): bool
    {
        $now = now()->timestamp;
        $windowStart = $now - 60;

        if (! isset(self::$rateCache[$key])) {
            return false;
        }

        $recentRequests = array_filter(
            self::$rateCache[$key],
            fn ($timestamp) => $timestamp > $windowStart,
        );

        return count($recentRequests) >= self::RATE_LIMIT_PER_MINUTE;
    }

    /**
     * Record a rate limit hit.
     */
    protected function recordRateLimit(string $key): void
    {
        if (! isset(self::$rateCache[$key])) {
            self::$rateCache[$key] = [];
        }

        self::$rateCache[$key][] = now()->timestamp;
    }

    /**
     * Record translation usage.
     */
    protected function recordUsage(
        Agency $agency,
        string $provider,
        string $model,
        string $sourceLang,
        string $targetLang,
        int $originalLength,
        int $translatedLength,
        int $tokens,
        float $costUsd,
    ): void {
        try {
            AiContentLog::create([
                'agency_id' => $agency->id,
                'provider' => $provider,
                'model' => $model,
                'action' => 'translate',
                'content_type' => 'translation',
                'prompt' => "Translate {$sourceLang} -> {$targetLang}",
                'response' => "Length: {$originalLength} -> {$translatedLength}",
                'total_tokens' => $tokens,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'cost_usd' => $costUsd,
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to record translation usage: {$e->getMessage()}");
        }
    }
}
