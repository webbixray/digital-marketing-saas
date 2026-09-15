<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Services\AI\AiContentService;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Exceptions\NoProviderAvailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiGatewayTest extends TestCase
{
    use RefreshDatabase;

    private AiGateway $gateway;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new AiGateway;
        $this->agency = Agency::factory()->create();
    }

    // ─── Provider Registration ───────────────────────────────────────────

    public function test_register_provider_stores_provider(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->andReturn(true);

        $this->gateway->registerProvider('openai', $provider);

        $this->assertCount(1, $this->gateway->getProviders());
        $this->assertEquals(['openai'], $this->gateway->getProviderNames());
    }

    public function test_has_available_provider_returns_true_when_provider_is_available(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->once()->andReturn(true);

        $this->gateway->registerProvider('openai', $provider);

        $this->assertTrue($this->gateway->hasAvailableProvider());
    }

    public function test_has_available_provider_returns_false_when_no_providers(): void
    {
        $this->assertFalse($this->gateway->hasAvailableProvider());
    }

    public function test_set_default_provider_changes_provider(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('send')->andReturn(new AiResponse(
            content: 'test',
            model: 'gpt-4o',
            provider: 'anthropic',
        ));
        $provider->shouldReceive('calculateCost')->andReturn(0.001);

        $this->gateway->registerProvider('anthropic', $provider);
        $this->gateway->setDefaultProvider('anthropic');

        $request = AiRequest::text('Hello');
        $response = $this->gateway->send($request, $this->agency);

        $this->assertEquals('anthropic', $response->provider);
    }

    // ─── Successful Response ─────────────────────────────────────────────

    public function test_send_returns_valid_response_from_provider(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->once()->andReturn(true);
        $provider->shouldReceive('send')->once()->andReturn(new AiResponse(
            content: 'Hello, world!',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            finishReason: 'stop',
        ));
        $provider->shouldReceive('calculateCost')->once()->andReturn(0.0015);

        $this->gateway->registerProvider('openai', $provider);

        $request = AiRequest::text('Hello');
        $response = $this->gateway->send($request, $this->agency);

        $this->assertInstanceOf(AiResponse::class, $response);
        $this->assertEquals('Hello, world!', $response->content);
        $this->assertEquals('gpt-4o', $response->model);
        $this->assertEquals('openai', $response->provider);
        $this->assertEquals(10, $response->promptTokens);
        $this->assertEquals(20, $response->completionTokens);
        $this->assertEquals(30, $response->totalTokens);
        $this->assertEquals(0.0015, $response->costUsd);
        $this->assertEquals('stop', $response->finishReason);
    }

    // ─── Error Handling ──────────────────────────────────────────────────

    public function test_send_throws_no_provider_available_when_all_fail(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->once()->andReturn(true);
        $provider->shouldReceive('send')->once()->andThrow(new \RuntimeException('API timeout'));
        $provider->shouldReceive('getName')->andReturn('openai');

        $this->gateway->registerProvider('openai', $provider);

        $request = AiRequest::text('Hello');

        $this->expectException(NoProviderAvailableException::class);
        $this->expectExceptionMessage('All AI providers failed');

        $this->gateway->send($request, $this->agency);
        $this->assertTrue(true, 'Expected exception was thrown');
    }

    public function test_send_skips_unavailable_providers(): void
    {
        $unavailableProvider = \Mockery::mock(AiProviderInterface::class);
        $unavailableProvider->shouldReceive('isAvailable')->once()->andReturn(false);

        $availableProvider = \Mockery::mock(AiProviderInterface::class);
        $availableProvider->shouldReceive('isAvailable')->once()->andReturn(true);
        $availableProvider->shouldReceive('send')->once()->andReturn(new AiResponse(
            content: 'Success from anthropic',
            model: 'claude-3',
            provider: 'anthropic',
        ));
        $availableProvider->shouldReceive('calculateCost')->once()->andReturn(0.002);

        $this->gateway->registerProvider('openai', $unavailableProvider);
        $this->gateway->registerProvider('anthropic', $availableProvider);

        $request = AiRequest::text('Hello');
        $response = $this->gateway->send($request, $this->agency);

        $this->assertEquals('Success from anthropic', $response->content);
        $this->assertEquals('anthropic', $response->provider);
    }

    // ─── Provider Fallback ───────────────────────────────────────────────

    public function test_send_falls_back_to_next_provider_on_failure(): void
    {
        $openai = \Mockery::mock(AiProviderInterface::class);
        $openai->shouldReceive('isAvailable')->once()->andReturn(true);
        $openai->shouldReceive('send')->once()->andThrow(new \RuntimeException('Rate limited'));
        $openai->shouldReceive('getName')->andReturn('openai');

        $anthropic = \Mockery::mock(AiProviderInterface::class);
        $anthropic->shouldReceive('isAvailable')->once()->andReturn(true);
        $anthropic->shouldReceive('send')->once()->andReturn(new AiResponse(
            content: 'Fallback response',
            model: 'claude-3-sonnet',
            provider: 'anthropic',
            promptTokens: 50,
            completionTokens: 100,
            totalTokens: 150,
        ));
        $anthropic->shouldReceive('calculateCost')->once()->andReturn(0.003);

        $this->gateway->registerProvider('openai', $openai);
        $this->gateway->registerProvider('anthropic', $anthropic);

        $request = AiRequest::text('Hello');
        $response = $this->gateway->send($request, $this->agency);

        $this->assertEquals('Fallback response', $response->content);
        $this->assertEquals('anthropic', $response->provider);
        $this->assertEquals(150, $response->totalTokens);
    }

    public function test_send_fails_when_no_providers_registered(): void
    {
        $request = AiRequest::text('Hello');

        $this->expectException(NoProviderAvailableException::class);

        $this->gateway->send($request, $this->agency);
        $this->assertTrue(true, 'Expected exception was thrown');
    }

    // ─── Task-based Routing ──────────────────────────────────────────────

    public function test_send_uses_task_routing_configuration(): void
    {
        config(['platform.ai.routing.fast' => ['openai:gpt-4o-mini', 'anthropic:claude-3-haiku']]);

        $openai = \Mockery::mock(AiProviderInterface::class);
        $openai->shouldReceive('isAvailable')->once()->andReturn(true);
        $openai->shouldReceive('send')->once()->andReturn(new AiResponse(
            content: 'Fast response',
            model: 'gpt-4o-mini',
            provider: 'openai',
        ));
        $openai->shouldReceive('calculateCost')->once()->andReturn(0.0005);

        $anthropic = \Mockery::mock(AiProviderInterface::class);
        $anthropic->shouldNotReceive('send');

        $this->gateway->registerProvider('openai', $openai);
        $this->gateway->registerProvider('anthropic', $anthropic);

        $request = AiRequest::text('Quick question', task: 'fast');
        $response = $this->gateway->send($request, $this->agency);

        $this->assertEquals('Fast response', $response->content);
    }

    // ─── AiContentService Integration (Logging) ──────────────────────────

    public function test_generate_creates_ai_content_log_record(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('send')->andReturn(new AiResponse(
            content: 'Generated content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 100,
            completionTokens: 200,
            totalTokens: 300,
        ));
        $provider->shouldReceive('calculateCost')->andReturn(0.0045);

        $this->gateway->registerProvider('openai', $provider);

        $service = new AiContentService($this->gateway);
        $service->generate($this->agency, 'Write a post about AI');

        $this->assertDatabaseHas('ai_content_logs', [
            'agency_id' => $this->agency->id,
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'action' => 'generate',
            'status' => 'success',
            'total_tokens' => 300,
            'prompt_tokens' => 100,
            'completion_tokens' => 200,
            'cost_usd' => 0.0045,
        ]);
    }

    public function test_generate_logs_failed_status_on_error(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('send')->andThrow(new \RuntimeException('API rate limit exceeded'));
        $provider->shouldReceive('getName')->andReturn('openai');

        $this->gateway->registerProvider('openai', $provider);

        $service = new AiContentService($this->gateway);

        try {
            $service->generate($this->agency, 'Write a post about AI');
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertDatabaseHas('ai_content_logs', [
            'agency_id' => $this->agency->id,
            'provider' => 'unknown',
            'action' => 'generate',
            'status' => 'failed',
            'total_tokens' => 0,
            'cost_usd' => 0,
        ]);

        $log = AiContentLog::where('agency_id', $this->agency->id)->where('status', 'failed')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('API rate limit exceeded', $log->error_message);
    }

    public function test_generate_calculates_cost_usd_correctly(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('send')->andReturn(new AiResponse(
            content: 'Content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 1000000, // 1M tokens
            completionTokens: 500000, // 0.5M tokens
            totalTokens: 1500000,
        ));
        $provider->shouldReceive('calculateCost')->andReturn(7.50); // $2.50 + $5.00

        $this->gateway->registerProvider('openai', $provider);

        $service = new AiContentService($this->gateway);
        $service->generate($this->agency, 'Prompt');

        $this->assertDatabaseHas('ai_content_logs', [
            'agency_id' => $this->agency->id,
            'cost_usd' => 7.50,
            'total_tokens' => 1500000,
        ]);
    }

    public function test_generate_logs_with_correct_content(): void
    {
        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('send')->andReturn(new AiResponse(
            content: 'AI response text',
            model: 'gpt-4o',
            provider: 'openai',
        ));
        $provider->shouldReceive('calculateCost')->andReturn(0.001);

        $this->gateway->registerProvider('openai', $provider);

        $service = new AiContentService($this->gateway);
        $service->generate($this->agency, 'User prompt text');

        $log = AiContentLog::where('agency_id', $this->agency->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('openai', $log->provider);
        $this->assertStringContainsString('User prompt text', $log->getRawOriginal('prompt'));
        $this->assertStringContainsString('AI response text', $log->getRawOriginal('response'));
    }

    // ─── Rate Limit Simulation ───────────────────────────────────────────

    public function test_gateway_handles_rate_limit_then_falls_back(): void
    {
        $openai = \Mockery::mock(AiProviderInterface::class);
        $openai->shouldReceive('isAvailable')->once()->andReturn(true);
        $openai->shouldReceive('send')->once()->andThrow(new \RuntimeException('HTTP 429 Too Many Requests'));
        $openai->shouldReceive('getName')->andReturn('openai');

        $anthropic = \Mockery::mock(AiProviderInterface::class);
        $anthropic->shouldReceive('isAvailable')->once()->andReturn(true);
        $anthropic->shouldReceive('send')->once()->andReturn(new AiResponse(
            content: 'Fallback content after rate limit',
            model: 'claude-3-opus',
            provider: 'anthropic',
        ));
        $anthropic->shouldReceive('calculateCost')->once()->andReturn(0.015);

        $this->gateway->registerProvider('openai', $openai);
        $this->gateway->registerProvider('anthropic', $anthropic);

        $request = AiRequest::text('Hello');
        $response = $this->gateway->send($request, $this->agency);

        $this->assertEquals('Fallback content after rate limit', $response->content);
        $this->assertEquals('anthropic', $response->provider);
    }

    public function test_gateway_throws_when_all_providers_rate_limited(): void
    {
        $openai = \Mockery::mock(AiProviderInterface::class);
        $openai->shouldReceive('isAvailable')->once()->andReturn(true);
        $openai->shouldReceive('send')->once()->andThrow(new \RuntimeException('Rate limited'));
        $openai->shouldReceive('getName')->andReturn('openai');

        $anthropic = \Mockery::mock(AiProviderInterface::class);
        $anthropic->shouldReceive('isAvailable')->once()->andReturn(true);
        $anthropic->shouldReceive('send')->once()->andThrow(new \RuntimeException('Rate limited'));
        $anthropic->shouldReceive('getName')->andReturn('anthropic');

        $this->gateway->registerProvider('openai', $openai);
        $this->gateway->registerProvider('anthropic', $anthropic);

        $request = AiRequest::text('Hello');

        $this->expectException(NoProviderAvailableException::class);

        $this->gateway->send($request, $this->agency);
        $this->assertTrue(true, 'Expected exception was thrown');
    }

    // ─── Multiple Provider Chain ─────────────────────────────────────────

    public function test_send_tries_multiple_providers_before_succeeding(): void
    {
        $callLog = [];

        $openai = \Mockery::mock(AiProviderInterface::class);
        $openai->shouldReceive('isAvailable')->andReturn(true);
        $openai->shouldReceive('send')->once()->andReturnUsing(function () use (&$callLog) {
            $callLog[] = 'openai';
            throw new \RuntimeException('Failed');
        });
        $openai->shouldReceive('getName')->andReturn('openai');

        $anthropic = \Mockery::mock(AiProviderInterface::class);
        $anthropic->shouldReceive('isAvailable')->andReturn(true);
        $anthropic->shouldReceive('send')->once()->andReturnUsing(function () use (&$callLog) {
            $callLog[] = 'anthropic';
            throw new \RuntimeException('Failed');
        });
        $anthropic->shouldReceive('getName')->andReturn('anthropic');

        $google = \Mockery::mock(AiProviderInterface::class);
        $google->shouldReceive('isAvailable')->once()->andReturn(true);
        $google->shouldReceive('send')->once()->andReturnUsing(function () use (&$callLog) {
            $callLog[] = 'google';

            return new AiResponse(
                content: 'Final success',
                model: 'gemini-pro',
                provider: 'google',
            );
        });
        $google->shouldReceive('calculateCost')->once()->andReturn(0.001);

        $this->gateway->registerProvider('openai', $openai);
        $this->gateway->registerProvider('anthropic', $anthropic);
        $this->gateway->registerProvider('google', $google);

        $request = AiRequest::text('Hello');
        $response = $this->gateway->send($request, $this->agency);

        $this->assertEquals('Final success', $response->content);
        $this->assertEquals(['openai', 'anthropic', 'google'], $callLog);
    }
}
