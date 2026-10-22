<?php

namespace App\Services\Localization;

use App\Models\Language;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Cache TTL in seconds (24 hours).
     */
    private const CACHE_TTL = 86400;

    /**
     * Translate text from source language to target language.
     */
    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        if ($sourceLang === $targetLang) {
            return $text;
        }

        $cacheKey = $this->getCacheKey($text, $sourceLang, $targetLang);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($text, $sourceLang, $targetLang) {
            try {
                return $this->callAiGateway($text, $sourceLang, $targetLang);
            } catch (\Throwable $e) {
                Log::warning('AI translation failed', [
                    'error' => $e->getMessage(),
                    'source' => $sourceLang,
                    'target' => $targetLang,
                ]);

                return $text;
            }
        });
    }

    /**
     * Call the AI gateway for translation.
     */
    private function callAiGateway(string $text, string $sourceLang, string $targetLang): string
    {
        $prompt = "Translate the following text from {$sourceLang} to {$targetLang}. "
            ."Return ONLY the translated text, no explanations or quotes.\n\n"
            .$text;

        $request = new AiRequest(
            prompt: $prompt,
            systemPrompt: null,
            model: 'gpt-4o',
            temperature: 0.1,
            maxTokens: max(256, strlen($text) * 2),
        );

        /** @var AiGateway $gateway */
        $gateway = app(AiGateway::class);
        $agency = auth()->user()?->agency;

        if (! $agency) {
            throw new \RuntimeException('No agency context available for translation');
        }

        $response = $gateway->send($request, $agency);

        return trim($response->content);
    }

    /**
     * Get all supported (active) languages.
     *
     * Falls back to an empty collection when the database is unavailable
     * (e.g. before migrations, during a DB outage) so that global view
     * composers and middleware never take the whole request down.
     *
     * @return Collection<int, Language>
     */
    public function getSupportedLanguages(): Collection
    {
        $cached = Cache::get('supported_languages');
        if ($cached !== null && $cached instanceof Collection) {
            return $cached;
        }

        try {
            $languages = Language::active()->orderBy('sort_order')->get();
            Cache::put('supported_languages', $languages, self::CACHE_TTL);

            return $languages;
        } catch (\Throwable $e) {
            // DB unavailable: return the fallback WITHOUT caching it, so the
            // next request retries once the database recovers.
            return collect();
        }
    }

    /**
     * RTL locales that do not depend on database state.
     *
     * Text direction is a property of the language itself, so a static list
     * is a correct fallback when the languages table cannot be read.
     */
    private const RTL_LOCALES = ['ar', 'he', 'fa', 'ur', 'dv', 'ps', 'sd', 'ug', 'yi'];

    /**
     * Check if a language is RTL.
     */
    public function isRTL(string $lang): bool
    {
        $cacheKey = "lang_rtl_{$lang}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (bool) $cached;
        }

        try {
            $isRtl = Language::byCode($lang)->where('is_rtl', true)->exists();
        } catch (\Throwable $e) {
            // DB unavailable: fall back to the static RTL list WITHOUT
            // caching, so the DB is retried on the next request.
            return in_array($lang, self::RTL_LOCALES, true);
        }

        Cache::put($cacheKey, $isRtl, self::CACHE_TTL);

        return $isRtl;
    }

    /**
     * Get the text direction for a language.
     */
    public function getLanguageDirection(string $lang): string
    {
        return $this->isRTL($lang) ? 'rtl' : 'ltr';
    }

    /**
     * Simple language detection based on common patterns.
     * Returns the most likely language code.
     */
    public function detectLanguage(string $text): string
    {
        // Basic heuristics for language detection
        if (preg_match('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}]/u', $text)) {
            return 'zh';
        }

        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text)) {
            return 'ja';
        }

        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $text)) {
            return 'ar';
        }

        if (preg_match('/[\x{1000}-\x{109F}]/u', $text)) {
            return 'my';
        }

        // Common word detection for Latin scripts
        $lowerText = mb_strtolower($text);

        $markers = [
            'de' => ['der', 'die', 'das', 'und', 'ist', 'ein', 'eine'],
            'fr' => ['le', 'la', 'les', 'est', 'un', 'une', 'des', 'et'],
            'es' => ['el', 'la', 'los', 'las', 'es', 'un', 'una', 'y'],
            'en' => ['the', 'is', 'are', 'and', 'of', 'to', 'in'],
        ];

        foreach ($markers as $lang => $words) {
            $matchCount = 0;
            foreach ($words as $word) {
                if (str_contains($lowerText, " {$word} ") || str_starts_with($lowerText, "{$word} ")) {
                    $matchCount++;
                }
            }
            if ($matchCount >= 2) {
                return $lang;
            }
        }

        return 'en';
    }

    /**
     * Clear translation cache for a specific language.
     */
    public function clearCacheForLanguage(string $lang): void
    {
        Cache::forget("lang_rtl_{$lang}");
        Cache::forget('supported_languages');
    }

    /**
     * Generate cache key for a translation.
     */
    private function getCacheKey(string $text, string $sourceLang, $targetLang): string
    {
        return 'translate_'.md5($sourceLang.'|'.$targetLang.'|'.$text);
    }
}
