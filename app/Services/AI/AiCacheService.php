<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Content-addressable AI response cache.
 *
 * Cache key = hash(agency_id + normalized prompt + model + temperature + max_tokens + system_prompt)
 * This ensures identical prompts return cached responses without API calls.
 */
class AiCacheService
{
    /**
     * TTL per task type (in seconds).
     * Analysis/reasoning = longer TTL (deterministic), creative = shorter.
     */
    private const TASK_TTL = [
        'fast' => 3600,       // 1 hour
        'creative' => 1800,   // 30 min (content freshness matters)
        'reasoning' => 7200,  // 2 hours (deterministic)
        'analysis' => 7200,   // 2 hours (deterministic)
        'default' => 3600,
    ];

    /**
     * Maximum cache size per agency.
     */
    private const MAX_CACHE_PER_AGENCY = 500;

    /**
     * Cache hit/miss metrics key.
     */
    private const METRICS_KEY = 'ai_cache_metrics';

    /**
     * Check if a cached response exists for the given request.
     */
    public function get(AiRequest $request, Agency $agency): ?AiResponse
    {
        $key = $this->generateCacheKey($request, $agency);
        $cached = Cache::get($key);

        if ($cached === null) {
            $this->recordMiss($agency->id);
            return null;
        }

        $this->recordHit($agency->id);

        Log::debug("AI cache hit: agency={$agency->id}, task={$request->task}, model={$request->model}");

        return $this->deserialize($cached);
    }

    /**
     * Store a response in cache.
     */
    public function put(AiRequest $request, Agency $agency, AiResponse $response): void
    {
        $key = $this->generateCacheKey($request, $agency);
        $ttl = self::TASK_TTL[$request->task] ?? self::TASK_TTL['default'];

        // Compress: don't cache if response is too large (> 50KB)
        if (strlen($response->content) > 50000) {
            Log::debug("AI cache skip: response too large ({$response->content} chars)");
            return;
        }

        Cache::put($key, $this->serialize($response), $ttl);

        // Register key for later invalidation
        $this->registerKey($agency->id, $key);

        // Track per-agency cache size
        $this->trackAgencyCacheSize($agency->id);

        Log::debug("AI cache stored: agency={$agency->id}, task={$request->task}, ttl={$ttl}s");
    }

    /**
     * Invalidate all cached responses for an agency.
     */
    public function invalidateAgency(int $agencyId): void
    {
        $pattern = "ai_response:{$agencyId}:*";
        $this->forgetByPattern($pattern);
        Log::info("AI cache invalidated for agency={$agencyId}");
    }

    /**
     * Invalidate cached responses by task type for an agency.
     */
    public function invalidateByTask(int $agencyId, string $task): void
    {
        $pattern = "ai_response:{$agencyId}:*:{$task}:*";
        $this->forgetByPattern($pattern);
    }

    /**
     * Get cache metrics (hit/miss ratio).
     */
    public function getMetrics(int $agencyId): array
    {
        $metrics = Cache::get(self::METRICS_KEY, []);
        $agencyMetrics = $metrics[$agencyId] ?? ['hits' => 0, 'misses' => 0, 'saved_usd' => 0.0];
        $total = $agencyMetrics['hits'] + $agencyMetrics['misses'];
        $agencyMetrics['hit_rate'] = $total > 0 ? round($agencyMetrics['hits'] / $total * 100, 1) : 0.0;
        $agencyMetrics['total_requests'] = $total;
        return $agencyMetrics;
    }

    /**
     * Get estimated cost savings from cache hits.
     */
    public function getSavings(int $agencyId): float
    {
        $metrics = Cache::get(self::METRICS_KEY, []);
        return $metrics[$agencyId]['saved_usd'] ?? 0.0;
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Generate a deterministic cache key from request parameters.
     */
    private function generateCacheKey(AiRequest $request, Agency $agency): string
    {
        $normalized = $this->normalizePrompt($request->prompt);
        $systemNormalized = $request->systemPrompt ? $this->normalizePrompt($request->systemPrompt) : '';

        $keyData = implode('|', [
            $agency->id,
            $normalized,
            $systemNormalized,
            $request->model,
            $request->temperature,
            $request->maxTokens,
            $request->task,
        ]);

        return 'ai_response:' . $agency->id . ':' . hash('sha256', $keyData) . ':' . $request->task;
    }

    /**
     * Normalize a prompt for deduplication:
     * - Trim whitespace
     * - Collapse multiple spaces/newlines
     * - Lowercase
     */
    private function normalizePrompt(string $prompt): string
    {
        $prompt = trim($prompt);
        $prompt = preg_replace('/\s+/', ' ', $prompt);
        return strtolower($prompt);
    }

    /**
     * Serialize an AiResponse for cache storage.
     */
    private function serialize(AiResponse $response): array
    {
        return [
            'content' => $response->content,
            'model' => $response->model,
            'provider' => $response->provider,
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'total_tokens' => $response->totalTokens,
            'cost_usd' => $response->costUsd,
            'finish_reason' => $response->finishReason,
            'metadata' => $response->metadata,
            'cached_at' => now()->toISOString(),
        ];
    }

    /**
     * Deserialize cached data back to AiResponse.
     */
    private function deserialize(array $data): AiResponse
    {
        return new AiResponse(
            content: $data['content'],
            model: $data['model'],
            provider: $data['provider'] . ':cached',
            promptTokens: $data['prompt_tokens'],
            completionTokens: $data['completion_tokens'],
            totalTokens: $data['total_tokens'],
            costUsd: 0.0, // Cache hit costs nothing
            finishReason: $data['finish_reason'],
            metadata: array_merge($data['metadata'] ?? [], ['cached' => true, 'cached_at' => $data['cached_at']]),
        );
    }

    private function recordHit(int $agencyId): void
    {
        $metrics = Cache::get(self::METRICS_KEY, []);
        if (! isset($metrics[$agencyId])) {
            $metrics[$agencyId] = ['hits' => 0, 'misses' => 0, 'saved_usd' => 0.0];
        }
        $metrics[$agencyId]['hits']++;
        $metrics[$agencyId]['saved_usd'] += 0.002; // Average cost per API call
        Cache::put(self::METRICS_KEY, $metrics, now()->addDays(30));
    }

    private function recordMiss(int $agencyId): void
    {
        $metrics = Cache::get(self::METRICS_KEY, []);
        if (! isset($metrics[$agencyId])) {
            $metrics[$agencyId] = ['hits' => 0, 'misses' => 0, 'saved_usd' => 0.0];
        }
        $metrics[$agencyId]['misses']++;
        Cache::put(self::METRICS_KEY, $metrics, now()->addDays(30));
    }

    private function trackAgencyCacheSize(int $agencyId): void
    {
        $key = "ai_cache_size:{$agencyId}";
        $size = Cache::get($key, 0);
        Cache::put($key, $size + 1, now()->addDay());
    }

    /**
     * Forget cache entries matching a pattern.
     * Uses a key registry for file/database cache drivers.
     */
    private function forgetByPattern(string $pattern): void
    {
        $agencyId = explode(':', $pattern)[1] ?? null;
        if (! $agencyId) {
            return;
        }

        $registryKey = "ai_cache_registry:{$agencyId}";
        $registry = Cache::get($registryKey, []);

        foreach ($registry as $key) {
            Cache::forget($key);
        }

        Cache::forget($registryKey);
    }

    /**
     * Track a cache key in the registry for later invalidation.
     */
    private function registerKey(int $agencyId, string $key): void
    {
        $registryKey = "ai_cache_registry:{$agencyId}";
        $registry = Cache::get($registryKey, []);
        $registry[] = $key;
        Cache::put($registryKey, array_unique($registry), now()->addDays(7));
    }
}
