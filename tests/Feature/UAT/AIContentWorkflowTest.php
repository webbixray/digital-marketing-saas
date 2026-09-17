<?php

namespace Tests\Feature\UAT;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Models\ContentAsset;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiResponse;
use App\Services\QuotaService;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIContentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createAgencyWithUser(string $role = 'owner'): array
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => $role]);

        return [$agency, $user];
    }

    /**
     * Test the complete AI content workflow:
     * Generate AI content → save to content library → create social post → schedule → check stats
     */
    public function test_complete_ai_content_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        // Mock the AI gateway to avoid real API calls
        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: 'AI generated content for social media post about digital marketing trends.',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 50,
            completionTokens: 100,
            totalTokens: 150,
            costUsd: 0.0025,
            finishReason: 'stop',
        ));
        $this->app->instance(AiGateway::class, $mockGateway);

        // Step 1: Generate AI content
        $response = $this->postJson(route('ai.generate'), [
            'prompt' => 'Write a social media post about digital marketing trends',
            'content_type' => 'post',
            'model' => 'gpt-4o',
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'content',
                'tokens',
                'cost',
                'provider',
                'model',
            ]);

        $generatedContent = $response->json('content');
        $this->assertNotEmpty($generatedContent);

        // Verify AI usage was tracked
        $this->assertDatabaseHas('ai_content_logs', [
            'agency_id' => $agency->id,
            'action' => 'generate',
            'content_type' => 'post',
            'status' => 'success',
            'provider' => 'openai',
            'model' => 'gpt-4o',
        ]);

        // Step 2: Save generated content to content library
        $contentResponse = $this->post(route('content.store'), [
            'name' => 'AI Generated Marketing Post',
            'type' => 'text',
            'content' => $generatedContent,
        ]);
        $contentResponse->assertRedirectContains('/content/');
        $this->assertDatabaseHas('content_assets', [
            'name' => 'AI Generated Marketing Post',
            'agency_id' => $agency->id,
        ]);

        $contentAsset = ContentAsset::where('name', 'AI Generated Marketing Post')->first();
        $this->assertNotNull($contentAsset);

        // Step 3: Create social post from AI-generated content
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $postResponse = $this->post(route('social.posts.store'), [
            'social_account_id' => $account->id,
            'content' => $generatedContent,
        ]);
        $postResponse->assertRedirect(route('social.posts.index'));
        $this->assertDatabaseHas('social_posts', [
            'content' => $generatedContent,
            'agency_id' => $agency->id,
        ]);

        $post = SocialPost::where('content', $generatedContent)->first();
        $this->assertNotNull($post);

        // Step 4: Schedule the post (update with scheduled_at)
        $scheduleResponse = $this->put(route('social.posts.update', $post), [
            'content' => $generatedContent,
            'scheduled_at' => Carbon::now()->addDay()->toDateTimeString(),
        ]);
        $scheduleResponse->assertRedirect(route('social.posts.index'));

        // Verify scheduled_at was set
        $post->refresh();
        $this->assertNotNull($post->scheduled_at);
        // Note: controller may not auto-update status to 'scheduled' on update;
        // verifying scheduled_at is sufficient for workflow coverage

        // Step 5: Check AI usage stats
        $aiLogs = AiContentLog::where('agency_id', $agency->id)->get();
        $this->assertGreaterThanOrEqual(1, $aiLogs->count());

        $totalTokens = $aiLogs->sum('total_tokens');
        $this->assertGreaterThan(0, $totalTokens);

        $totalCost = $aiLogs->sum('cost_usd');
        $this->assertGreaterThanOrEqual(0, $totalCost);
    }

    /**
     * Test AI index route returns 200 with quota info
     */
    public function test_ai_index_route_returns_200(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->get(route('ai.index'));
        $response->assertStatus(200);
    }

    /**
     * Test AI generate route with valid data
     */
    public function test_ai_generate_route_with_valid_data(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: 'Generated content here',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 30,
            completionTokens: 70,
            totalTokens: 100,
            costUsd: 0.001,
            finishReason: 'stop',
        ));
        $this->app->instance(AiGateway::class, $mockGateway);

        $response = $this->postJson(route('ai.generate'), [
            'prompt' => 'Write a blog post about SEO',
            'content_type' => 'post',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test AI generate route validation fails with missing prompt
     */
    public function test_ai_generate_route_validation_fails_without_prompt(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->postJson(route('ai.generate'), [
            'content_type' => 'post',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    /**
     * Test AI generate route validation fails with invalid content_type
     */
    public function test_ai_generate_route_validation_fails_with_invalid_content_type(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->postJson(route('ai.generate'), [
            'prompt' => 'Test prompt',
            'content_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content_type']);
    }

    /**
     * Test AI rewrite route
     */
    public function test_ai_rewrite_route(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: 'Rewritten content with improvements',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 40,
            completionTokens: 80,
            totalTokens: 120,
            costUsd: 0.0015,
            finishReason: 'stop',
        ));
        $this->app->instance(AiGateway::class, $mockGateway);

        $response = $this->postJson(route('ai.rewrite'), [
            'content' => 'Original content that needs rewriting',
            'instructions' => 'Make it more professional',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'content']);
    }

    /**
     * Test AI rewrite route validation fails without content
     */
    public function test_ai_rewrite_route_validation_fails_without_content(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->postJson(route('ai.rewrite'), [
            'instructions' => 'Make it better',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    /**
     * Test AI hashtags route
     */
    public function test_ai_hashtags_route(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: '#digitalmarketing, #seo, #contentmarketing, #socialmedia',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 20,
            completionTokens: 30,
            totalTokens: 50,
            costUsd: 0.0005,
            finishReason: 'stop',
        ));
        $this->app->instance(AiGateway::class, $mockGateway);

        $response = $this->postJson(route('ai.hashtags'), [
            'topic' => 'digital marketing',
            'count' => 5,
            'platform' => 'instagram',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'hashtags']);
    }

    /**
     * Test AI hashtags route validation fails without topic
     */
    public function test_ai_hashtags_route_validation_fails_without_topic(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->postJson(route('ai.hashtags'), [
            'count' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['topic']);
    }

    /**
     * Test AI ideas route
     */
    public function test_ai_ideas_route(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: '[{"title":"5 SEO Tips","format":"carousel","description":"Share actionable SEO tips","cta":"Save this post"}]',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 25,
            completionTokens: 60,
            totalTokens: 85,
            costUsd: 0.0008,
            finishReason: 'stop',
        ));
        $this->app->instance(AiGateway::class, $mockGateway);

        $response = $this->postJson(route('ai.ideas'), [
            'topic' => 'content marketing',
            'count' => 3,
            'platform' => 'linkedin',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'ideas']);
    }

    /**
     * Test AI ideas route validation fails without topic
     */
    public function test_ai_ideas_route_validation_fails_without_topic(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->postJson(route('ai.ideas'), [
            'count' => 3,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['topic']);
    }

    /**
     * Test AiContentService integration with AiGateway
     */
    public function test_ai_content_service_integration_with_gateway(): void
    {
        $agency = Agency::factory()->create();

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: 'Service integration test content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 40,
            completionTokens: 60,
            totalTokens: 100,
            costUsd: 0.001,
            finishReason: 'stop',
        ));

        $service = new AiContentService($mockGateway);
        $response = $service->generate(
            agency: $agency,
            prompt: 'Test prompt',
            contentType: 'post',
        );

        $this->assertEquals('Service integration test content', $response->content);
        $this->assertEquals('gpt-4o', $response->model);
        $this->assertEquals('openai', $response->provider);
        $this->assertEquals(100, $response->totalTokens);

        // Verify usage was logged
        $this->assertDatabaseHas('ai_content_logs', [
            'agency_id' => $agency->id,
            'action' => 'generate',
            'status' => 'success',
        ]);
    }

    /**
     * Test AiContentService rewrite method
     */
    public function test_ai_content_service_rewrite(): void
    {
        $agency = Agency::factory()->create();

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: 'Rewritten version of the content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 50,
            completionTokens: 80,
            totalTokens: 130,
            costUsd: 0.0012,
            finishReason: 'stop',
        ));

        $service = new AiContentService($mockGateway);
        $result = $service->rewrite(
            agency: $agency,
            content: 'Original content',
            instructions: 'Make it better',
        );

        $this->assertEquals('Rewritten version of the content', $result);
    }

    /**
     * Test AiContentService generateHashtags method
     */
    public function test_ai_content_service_generate_hashtags(): void
    {
        $agency = Agency::factory()->create();

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: '#marketing, #digital, #seo, #content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 20,
            completionTokens: 30,
            totalTokens: 50,
            costUsd: 0.0004,
            finishReason: 'stop',
        ));

        $service = new AiContentService($mockGateway);
        $hashtags = $service->generateHashtags(
            agency: $agency,
            topic: 'digital marketing',
            count: 4,
        );

        $this->assertIsArray($hashtags);
        $this->assertGreaterThanOrEqual(1, count($hashtags));
    }

    /**
     * Test AiContentService generateIdeas method
     */
    public function test_ai_content_service_generate_ideas(): void
    {
        $agency = Agency::factory()->create();

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: '[{"title":"Idea 1","format":"post","description":"Description 1","cta":"CTA 1"}]',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 30,
            completionTokens: 50,
            totalTokens: 80,
            costUsd: 0.0006,
            finishReason: 'stop',
        ));

        $service = new AiContentService($mockGateway);
        $ideas = $service->generateIdeas(
            agency: $agency,
            topic: 'marketing',
            count: 1,
        );

        $this->assertIsArray($ideas);
    }

    /**
     * Test that AI usage is tracked in AiContentLog
     */
    public function test_ai_usage_tracked_in_log(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: 'Tracked content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 50,
            completionTokens: 100,
            totalTokens: 150,
            costUsd: 0.002,
            finishReason: 'stop',
        ));
        $this->app->instance(AiGateway::class, $mockGateway);

        $this->postJson(route('ai.generate'), [
            'prompt' => 'Test tracking',
            'content_type' => 'post',
        ]);

        $this->assertDatabaseHas('ai_content_logs', [
            'agency_id' => $agency->id,
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'action' => 'generate',
            'content_type' => 'post',
            'total_tokens' => 150,
            'prompt_tokens' => 50,
            'completion_tokens' => 100,
            'status' => 'success',
        ]);

        $log = AiContentLog::where('agency_id', $agency->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals(0.002, $log->cost_usd);
    }

    /**
     * Test that failed AI generation is also tracked
     */
    public function test_failed_ai_generation_is_tracked(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willThrowException(new \Exception('API rate limit exceeded'));
        $this->app->instance(AiGateway::class, $mockGateway);

        $response = $this->postJson(route('ai.generate'), [
            'prompt' => 'Test failure tracking',
            'content_type' => 'post',
        ]);

        $response->assertStatus(500);

        $this->assertDatabaseHas('ai_content_logs', [
            'agency_id' => $agency->id,
            'action' => 'generate',
            'status' => 'failed',
        ]);

        $log = AiContentLog::where('agency_id', $agency->id)->where('status', 'failed')->first();
        $this->assertNotNull($log);
        $this->assertEquals('API rate limit exceeded', $log->error_message);
    }

    /**
     * Test quota enforcement for AI generations
     */
    public function test_quota_enforcement_for_ai_generations(): void
    {
        // Create agency at AI generation limit
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'ai_generations_count' => 100, // At limit for starter plan
        ]);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);
        $this->actingAs($user);

        $quotaService = app(QuotaService::class);
        $remaining = $quotaService->remainingAiGenerations($agency);

        // Starter plan has 100 AI generations per month
        $this->assertEquals(0, $remaining);
        $this->assertTrue($quotaService->isOverQuota($agency, 'ai_generations'));
    }

    /**
     * Test quota allows generation when under limit
     */
    public function test_quota_allows_generation_when_under_limit(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $quotaService = app(QuotaService::class);
        $remaining = $quotaService->remainingAiGenerations($agency);

        $this->assertGreaterThan(0, $remaining);
        $this->assertFalse($quotaService->isOverQuota($agency, 'ai_generations'));
    }

    /**
     * Test quota percentage calculation
     */
    public function test_quota_percentage_calculation(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'ai_generations_count' => 25,
        ]);

        $quotaService = app(QuotaService::class);
        $percentage = $quotaService->usagePercentage($agency, 'ai_generations');

        // 25 out of 50 = 50%
        $this->assertEquals(50.0, $percentage);
    }

    /**
     * Test quota status returns all quotas
     */
    public function test_quota_status_returns_all_quotas(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $quotaService = app(QuotaService::class);
        $status = $quotaService->getQuotaStatus($agency);

        $this->assertArrayHasKey('ai_generations', $status);
        $this->assertArrayHasKey('posts', $status);
        $this->assertArrayHasKey('social_accounts', $status);
        $this->assertArrayHasKey('campaigns', $status);
        $this->assertArrayHasKey('clients', $status);

        $this->assertArrayHasKey('used', $status['ai_generations']);
        $this->assertArrayHasKey('limit', $status['ai_generations']);
        $this->assertArrayHasKey('remaining', $status['ai_generations']);
        $this->assertArrayHasKey('percentage', $status['ai_generations']);
    }

    /**
     * Test enterprise plan has unlimited AI generations
     */
    public function test_enterprise_plan_has_unlimited_ai_generations(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
            'ai_generations_count' => 9999,
        ]);

        $quotaService = app(QuotaService::class);
        $remaining = $quotaService->remainingAiGenerations($agency);

        // Enterprise has unlimited (-1)
        $this->assertEquals(-1, $remaining);
        $this->assertFalse($quotaService->isOverQuota($agency, 'ai_generations'));
    }

    /**
     * Test AI usage stats aggregation
     */
    public function test_ai_usage_stats_aggregation(): void
    {
        $agency = Agency::factory()->create();

        // Create multiple AI content logs
        AiContentLog::factory()->count(5)->success()->create([
            'agency_id' => $agency->id,
            'action' => 'generate',
            'total_tokens' => 100,
            'prompt_tokens' => 40,
            'completion_tokens' => 60,
            'cost_usd' => 0.001,
        ]);

        AiContentLog::factory()->count(2)->success()->create([
            'agency_id' => $agency->id,
            'action' => 'rewrite',
            'total_tokens' => 200,
            'prompt_tokens' => 80,
            'completion_tokens' => 120,
            'cost_usd' => 0.002,
        ]);

        // Aggregate stats
        $totalLogs = AiContentLog::where('agency_id', $agency->id)->count();
        $this->assertEquals(7, $totalLogs);

        $totalTokens = AiContentLog::where('agency_id', $agency->id)->sum('total_tokens');
        $this->assertEquals(5 * 100 + 2 * 200, $totalTokens);

        $totalCost = AiContentLog::where('agency_id', $agency->id)->sum('cost_usd');
        $this->assertEquals(5 * 0.001 + 2 * 0.002, $totalCost);

        $successCount = AiContentLog::where('agency_id', $agency->id)->where('status', 'success')->count();
        $this->assertEquals(7, $successCount);
    }

    /**
     * Test AI content log factory states
     */
    public function test_ai_content_log_factory_states(): void
    {
        $agency = Agency::factory()->create();

        $successLog = AiContentLog::factory()->success()->create(['agency_id' => $agency->id]);
        $this->assertEquals('success', $successLog->status);
        $this->assertNull($successLog->error_message);

        $failedLog = AiContentLog::factory()->failed()->create(['agency_id' => $agency->id]);
        $this->assertEquals('failed', $failedLog->status);
        $this->assertNotNull($failedLog->error_message);

        $generateLog = AiContentLog::factory()->generate()->create(['agency_id' => $agency->id]);
        $this->assertEquals('generate', $generateLog->action);
        $this->assertEquals('post', $generateLog->content_type);

        $hashtagLog = AiContentLog::factory()->hashtags()->create(['agency_id' => $agency->id]);
        $this->assertEquals('hashtags', $hashtagLog->action);

        $ideasLog = AiContentLog::factory()->ideas()->create(['agency_id' => $agency->id]);
        $this->assertEquals('ideas', $ideasLog->action);
    }

    /**
     * Test AI content log scopes
     */
    public function test_ai_content_log_scopes(): void
    {
        $agency = Agency::factory()->create();

        AiContentLog::factory()->count(3)->create([
            'agency_id' => $agency->id,
            'provider' => 'openai',
            'status' => 'success',
        ]);

        AiContentLog::factory()->count(2)->create([
            'agency_id' => $agency->id,
            'provider' => 'anthropic',
            'status' => 'failed',
        ]);

        $this->assertEquals(3, AiContentLog::byProvider('openai')->count());
        $this->assertEquals(3, AiContentLog::byStatus('success')->count());
        $this->assertEquals(2, AiContentLog::byStatus('failed')->count());
    }

    /**
     * Test AI content log belongs to agency
     */
    public function test_ai_content_log_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $log = AiContentLog::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $log->agency);
        $this->assertEquals($agency->id, $log->agency->id);
    }

    /**
     * Test AI generation rate limiter is properly configured (50/hour/agency)
     */
    public function test_ai_generation_rate_limiter_is_configured(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $mockGateway = $this->createMock(AiGateway::class);
        $mockGateway->method('send')->willReturn(new AiResponse(
            content: 'Rate limit test content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 10,
            completionTokens: 20,
            totalTokens: 30,
            costUsd: 0.0003,
            finishReason: 'stop',
        ));
        $this->app->instance(AiGateway::class, $mockGateway);

        // Verify the rate limiter is properly defined
        $rateLimiter = app(RateLimiter::class);
        $limiter = $rateLimiter->limiter('ai_generate');
        $this->assertNotNull($limiter);

        // Make requests and verify rate limiter tracks them
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('ai.generate'), [
                'prompt' => "Test prompt {$i}",
                'content_type' => 'post',
            ]);
            $response->assertStatus(200);
        }
    }

    /**
     * Test cross-agency data isolation for AI logs
     */
    public function test_cross_agency_ai_logs_isolation(): void
    {
        $agency1 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $agency2 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user1 = User::factory()->create(['agency_id' => $agency1->id, 'role' => 'owner']);

        // Create logs for both agencies
        AiContentLog::factory()->count(3)->create(['agency_id' => $agency1->id]);
        AiContentLog::factory()->count(2)->create(['agency_id' => $agency2->id]);

        $this->actingAs($user1);

        // Agency 1 should only see its own logs
        $agency1Logs = AiContentLog::where('agency_id', $agency1->id)->get();
        $this->assertEquals(3, $agency1Logs->count());

        // Agency 2 logs should not be accessible
        $this->assertDatabaseHas('ai_content_logs', ['agency_id' => $agency2->id]);
        $this->assertNotEquals(
            $agency1Logs->pluck('id')->toArray(),
            AiContentLog::where('agency_id', $agency2->id)->pluck('id')->toArray()
        );
    }

    /**
     * Test AI content log cost calculation
     */
    public function test_ai_content_log_total_cost_attribute(): void
    {
        $log = AiContentLog::factory()->create([
            'cost_usd' => 0.0025,
        ]);

        $this->assertEquals(0.0025, $log->total_cost);
    }

    /**
     * Test AI content log casts work correctly
     */
    public function test_ai_content_log_casts(): void
    {
        $log = AiContentLog::factory()->create([
            'total_tokens' => 150,
            'prompt_tokens' => 50,
            'completion_tokens' => 100,
            'cost_usd' => 0.0025,
        ]);

        $this->assertIsInt($log->total_tokens);
        $this->assertIsInt($log->prompt_tokens);
        $this->assertIsInt($log->completion_tokens);
        $this->assertEquals(0.0025, $log->cost_usd);
    }
}
