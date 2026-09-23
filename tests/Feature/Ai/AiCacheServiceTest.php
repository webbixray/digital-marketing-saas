<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\AiCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private AiCacheService $cache;
    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new AiCacheService();
        $this->agency = Agency::factory()->create();
    }

    public function test_cache_miss_returns_null(): void
    {
        $request = new AiRequest(prompt: 'test prompt', task: 'fast');
        $result = $this->cache->get($request, $this->agency);
        $this->assertNull($result);
    }

    public function test_cache_hit_returns_stored_response(): void
    {
        $request = new AiRequest(prompt: 'What is AI?', task: 'fast', model: 'gpt-4o');
        $response = new \App\Services\AI\Gateway\AiResponse(
            content: 'AI is artificial intelligence.',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            costUsd: 0.001,
            finishReason: 'stop',
        );

        $this->cache->put($request, $this->agency, $response);
        $cached = $this->cache->get($request, $this->agency);

        $this->assertNotNull($cached);
        $this->assertEquals('AI is artificial intelligence.', $cached->content);
        $this->assertEquals(0.0, $cached->costUsd);
        $this->assertStringContainsString('cached', $cached->provider);
    }

    public function test_cache_isolation_between_agencies(): void
    {
        $agency2 = Agency::factory()->create();
        $request = new AiRequest(prompt: 'test prompt', task: 'fast');
        $response = new \App\Services\AI\Gateway\AiResponse(
            content: 'Agency 1 response',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            costUsd: 0.001,
            finishReason: 'stop',
        );

        $this->cache->put($request, $this->agency, $response);
        $this->assertNull($this->cache->get($request, $agency2));
    }

    public function test_prompt_normalization_for_dedup(): void
    {
        $request1 = new AiRequest(prompt: '  What   is  AI?  ', task: 'fast');
        $request2 = new AiRequest(prompt: 'what is ai?', task: 'fast');
        $response = new \App\Services\AI\Gateway\AiResponse(
            content: 'AI definition',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            costUsd: 0.001,
            finishReason: 'stop',
        );

        $this->cache->put($request1, $this->agency, $response);
        $cached = $this->cache->get($request2, $this->agency);

        $this->assertNotNull($cached);
        $this->assertEquals('AI definition', $cached->content);
    }

    public function test_different_models_cached_separately(): void
    {
        $request1 = new AiRequest(prompt: 'test', model: 'gpt-4o', task: 'fast');
        $request2 = new AiRequest(prompt: 'test', model: 'gpt-4o-mini', task: 'fast');
        $response = new \App\Services\AI\Gateway\AiResponse(
            content: 'Response',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            costUsd: 0.001,
            finishReason: 'stop',
        );

        $this->cache->put($request1, $this->agency, $response);
        $this->assertNull($this->cache->get($request2, $this->agency));
    }

    public function test_cache_invalidation(): void
    {
        $request = new AiRequest(prompt: 'test', task: 'fast');
        $response = new \App\Services\AI\Gateway\AiResponse(
            content: 'Response',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            costUsd: 0.001,
            finishReason: 'stop',
        );

        $this->cache->put($request, $this->agency, $response);
        $this->cache->invalidateAgency($this->agency->id);
        $this->assertNull($this->cache->get($request, $this->agency));
    }

    public function test_cache_metrics_tracking(): void
    {
        $request = new AiRequest(prompt: 'test', task: 'fast');
        $response = new \App\Services\AI\Gateway\AiResponse(
            content: 'Response',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            costUsd: 0.001,
            finishReason: 'stop',
        );

        $this->cache->get($request, $this->agency);
        $this->cache->put($request, $this->agency, $response);
        $this->cache->get($request, $this->agency);

        $metrics = $this->cache->getMetrics($this->agency->id);
        $this->assertEquals(1, $metrics['hits']);
        $this->assertEquals(1, $metrics['misses']);
        $this->assertEquals(50.0, $metrics['hit_rate']);
    }

    public function test_large_responses_not_cached(): void
    {
        $largeContent = str_repeat('x', 50001);
        $request = new AiRequest(prompt: 'test', task: 'fast');
        $response = new \App\Services\AI\Gateway\AiResponse(
            content: $largeContent,
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 100,
            completionTokens: 50000,
            totalTokens: 50100,
            costUsd: 0.05,
            finishReason: 'stop',
        );

        $this->cache->put($request, $this->agency, $response);
        $this->assertNull($this->cache->get($request, $this->agency));
    }
}
