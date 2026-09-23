<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GenerateAiContentJob;
use App\Models\Agency;
use App\Models\AiContentLog;
use App\Services\AI\AiContentService;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Exceptions\RateLimitException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class GenerateAiContentJobTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
    }

    public function test_generate_ai_content_job_calls_ai_gateway(): void
    {
        $response = new AiResponse(
            content: 'Generated AI content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 50,
            completionTokens: 150,
            totalTokens: 200,
            costUsd: 0.002,
            finishReason: 'stop',
            metadata: [],
        );

        $mockService = \Mockery::mock(AiContentService::class);
        $mockService->shouldReceive('generate')
            ->once()
            ->withArgs(function ($agency, $prompt, $contentType, $systemPrompt, $model, $task, $temperature, $maxTokens) {
                return $agency->id === $this->agency->id
                    && $prompt === 'Write a blog post'
                    && $contentType === 'post'
                    && $model === 'gpt-4o';
            })
            ->andReturn($response);

        $job = new GenerateAiContentJob(
            agency: $this->agency,
            prompt: 'Write a blog post',
        );
        $job->handle($mockService);

        // Verify the method was called (Mockery verifies this)
        $this->assertTrue(true);
    }

    public function test_generate_ai_content_job_stores_result(): void
    {
        $log = AiContentLog::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending',
        ]);

        $response = new AiResponse(
            content: 'AI generated response text',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 100,
            completionTokens: 500,
            totalTokens: 600,
            costUsd: 0.005,
            finishReason: 'stop',
            metadata: [],
        );

        $mockService = \Mockery::mock(AiContentService::class);
        $mockService->shouldReceive('generate')
            ->once()
            ->andReturn($response);

        $job = new GenerateAiContentJob(
            agency: $this->agency,
            prompt: 'Test prompt',
            logId: $log->id,
        );
        $job->handle($mockService);

        $log->refresh();
        $this->assertEquals('completed', $log->status);
        $this->assertEquals('openai', $log->provider);
        $this->assertEquals('gpt-4o', $log->model);
        $this->assertEquals(600, $log->total_tokens);
        // Verify the response was stored (cast as array, so stored as JSON)
        $rawResponse = $log->getRawOriginal('response');
        $this->assertNotNull($rawResponse);
        $this->assertStringContainsString('AI generated response text', $rawResponse);
    }

    public function test_generate_ai_content_job_handles_rate_limits(): void
    {
        $mockService = \Mockery::mock(AiContentService::class);
        $mockService->shouldReceive('generate')
            ->once()
            ->andThrow(new RateLimitException('Rate limit exceeded for provider: openai'));

        $job = new GenerateAiContentJob(
            agency: $this->agency,
            prompt: 'Test prompt',
        );

        try {
            $job->handle($mockService);
            $this->fail('Expected RateLimitException to be thrown');
        } catch (RateLimitException $e) {
            $this->assertEquals('Rate limit exceeded for provider: openai', $e->getMessage());
        }
    }

    public function test_generate_ai_content_job_handles_failures(): void
    {
        $log = AiContentLog::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending',
        ]);

        $job = new GenerateAiContentJob(
            agency: $this->agency,
            prompt: 'Test prompt',
            logId: $log->id,
        );

        $exception = new \RuntimeException('AI service permanently unavailable');
        $job->failed($exception);

        $log->refresh();
        $this->assertEquals('failed', $log->status);
        $this->assertEquals('AI service permanently unavailable', $log->error_message);
    }

    public function test_generate_ai_content_job_is_queueable(): void
    {
        $job = new GenerateAiContentJob(
            agency: $this->agency,
            prompt: 'Test prompt',
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->backoff);
        $this->assertEquals(300, $job->timeout);
    }

    public function test_generate_ai_content_job_dispatches_successfully(): void
    {
        Bus::fake();

        GenerateAiContentJob::dispatch(
            agency: $this->agency,
            prompt: 'Generate content',
        );

        Bus::assertDispatched(GenerateAiContentJob::class, function ($job) {
            return $job->agency->id === $this->agency->id
                && $job->prompt === 'Generate content'
                && $job->contentType === 'post';
        });
    }

    public function test_generate_ai_content_job_handles_generic_exception(): void
    {
        $mockService = \Mockery::mock(AiContentService::class);
        $mockService->shouldReceive('generate')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected AI error'));

        $job = new GenerateAiContentJob(
            agency: $this->agency,
            prompt: 'Test prompt',
        );

        try {
            $job->handle($mockService);
            $this->fail('Expected RuntimeException to be thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Unexpected AI error', $e->getMessage());
        }
    }
}
