<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    public function test_docs_page_returns_200(): void
    {
        $response = $this->get('/api/v1/docs');

        $response->assertStatus(200);
        $response->assertSee('swagger-ui', false);
        $response->assertSee('Digital Marketing SaaS API Documentation', false);
        $response->assertSee('swagger-ui-bundle.js', false);
    }

    public function test_openapi_spec_returns_valid_json(): void
    {
        $response = $this->get('/api/v1/docs/openapi.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();

        $this->assertIsArray($data);
        $this->assertEquals('3.0.3', $data['openapi']);
        $this->assertEquals('Digital Marketing SaaS API', $data['info']['title']);
        $this->assertArrayHasKey('paths', $data);
        $this->assertArrayHasKey('components', $data);
        $this->assertArrayHasKey('securitySchemes', $data['components']);
        $this->assertArrayHasKey('bearerAuth', $data['components']['securitySchemes']);
    }

    public function test_postman_collection_returns_valid_json(): void
    {
        $response = $this->get('/api/v1/docs/postman');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('item', $data);
        $this->assertArrayHasKey('auth', $data);
        $this->assertEquals('bearer', $data['auth']['type']);
        $this->assertEquals('Digital Marketing SaaS API', $data['info']['name']);
    }

    public function test_openapi_spec_contains_expected_paths(): void
    {
        $response = $this->get('/api/v1/docs/openapi.json');
        $spec = $response->json();

        $paths = $spec['paths'];

        // Social Accounts
        $this->assertArrayHasKey('/accounts', $paths);
        $this->assertArrayHasKey('/accounts/{account}', $paths);

        // Posts
        $this->assertArrayHasKey('/posts', $paths);
        $this->assertArrayHasKey('/posts/{post}', $paths);

        // Campaigns
        $this->assertArrayHasKey('/campaigns', $paths);
        $this->assertArrayHasKey('/campaigns/{campaign}', $paths);

        // Analytics
        $this->assertArrayHasKey('/analytics/cross-platform', $paths);
        $this->assertArrayHasKey('/analytics/growth', $paths);

        // Reports
        $this->assertArrayHasKey('/reports', $paths);
        $this->assertArrayHasKey('/reports/{report}', $paths);

        // Billing
        $this->assertArrayHasKey('/invoices', $paths);
        $this->assertArrayHasKey('/ai/credits/balance', $paths);

        // Integrations
        $this->assertArrayHasKey('/ai/generate', $paths);
        $this->assertArrayHasKey('/agents', $paths);
        $this->assertArrayHasKey('/workflows', $paths);

        // Agency
        $this->assertArrayHasKey('/agency/settings', $paths);
        $this->assertArrayHasKey('/rbac/roles', $paths);
    }

    public function test_openapi_spec_has_security_schemes(): void
    {
        $response = $this->get('/api/v1/docs/openapi.json');
        $spec = $response->json();

        $this->assertArrayHasKey('securitySchemes', $spec['components']);
        $this->assertEquals('http', $spec['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertEquals('bearer', $spec['components']['securitySchemes']['bearerAuth']['scheme']);
    }

    public function test_openapi_spec_has_error_responses(): void
    {
        $response = $this->get('/api/v1/docs/openapi.json');
        $spec = $response->json();

        $responses = $spec['components']['responses'];

        $this->assertArrayHasKey('Unauthorized', $responses);
        $this->assertArrayHasKey('Forbidden', $responses);
        $this->assertArrayHasKey('NotFound', $responses);
        $this->assertArrayHasKey('ValidationError', $responses);
        $this->assertArrayHasKey('TooManyRequests', $responses);
    }

    public function test_openapi_spec_has_tags(): void
    {
        $response = $this->get('/api/v1/docs/openapi.json');
        $spec = $response->json();

        $tagNames = array_column($spec['tags'], 'name');

        $this->assertContains('Authentication', $tagNames);
        $this->assertContains('Social Accounts', $tagNames);
        $this->assertContains('Posts', $tagNames);
        $this->assertContains('Campaigns', $tagNames);
        $this->assertContains('Analytics', $tagNames);
        $this->assertContains('Reports', $tagNames);
        $this->assertContains('Billing', $tagNames);
        $this->assertContains('Integrations', $tagNames);
        $this->assertContains('Agency', $tagNames);
    }
}
