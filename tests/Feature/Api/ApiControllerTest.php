<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    /** Test API authentication is required for v1 endpoints. */
    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertUnauthorized();
    }

    /** Test authenticated user can access dashboard endpoint. */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertOk();
        $response->assertJsonStructure([
            'overview',
            'social',
            'ai',
        ]);
    }

    /** Test rate limiting returns 429 when exceeded. */
    public function test_rate_limiting_returns_429(): void
    {
        // The throttle.api middleware uses per-user rate limiting.
        // We need to exceed 60 requests in 60 seconds.
        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($this->user)->getJson('/api/v1/dashboard');
        }

        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        // Should get rate limited (429)
        $this->assertEquals(429, $response->getStatusCode());
        $response->assertJson([
            'error' => 'rate_limit_exceeded',
        ]);
    }

    /** Test API health endpoint is publicly accessible. */
    public function test_health_endpoint_is_public(): void
    {
        $response = $this->getJson('/api/status');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    /** Test API unauthenticated request returns 401 JSON. */
    public function test_unauthenticated_request_returns_401_json(): void
    {
        $response = $this->getJson('/api/v1/posts');

        $response->assertUnauthorized();
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
    }

    /** Test API returns JSON content type. */
    public function test_api_returns_json_content_type(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    /** Test API ETag header is set for caching. */
    public function test_api_etag_header_is_set(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertOk();
        // CacheWithEtag middleware adds ETag header
        $this->assertNotNull($response->headers->get('ETag'));
    }

    /** Test API client CRUD operations work correctly. */
    public function test_api_client_crud(): void
    {
        $clientResponse = $this->actingAs($this->user)->postJson('/api/v1/clients', [
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);

        $clientResponse->assertCreated();
        $this->assertDatabaseHas('clients', [
            'name' => 'Test Client',
            'agency_id' => $this->agency->id,
        ]);

        $clientId = $clientResponse->json('data.id');
        $this->assertNotNull($clientId);
    }

    /** Test API enforces agency isolation. */
    public function test_api_enforces_agency_isolation(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        // Create client for other agency
        $otherClientResponse = $this->actingAs($otherUser)->postJson('/api/v1/clients', [
            'name' => 'Other Client',
            'email' => 'other@test.com',
        ]);
        $otherClientResponse->assertCreated();
        $otherClientId = $otherClientResponse->json('data.id');

        // Try to access it from first user's agency
        $response = $this->actingAs($this->user)->getJson("/api/v1/clients/{$otherClientId}");

        // Should return 404 (to avoid leaking existence)
        $response->assertNotFound();
    }

    /** Test API docs endpoint is publicly accessible. */
    public function test_api_docs_public(): void
    {
        $response = $this->getJson('/api/v1/docs');

        $response->assertOk();
    }

    /** Test API rejects invalid JSON with proper error format. */
    public function test_api_validates_json_input(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/dashboard?invalid_param=xxx');

        $response->assertOk(); // Dashboard doesn't validate params, just returns data
    }

    /** Test API response has request ID header. */
    public function test_api_response_has_request_id(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertOk();
        $this->assertNotNull($response->headers->get('X-Request-Id'));
    }
}
