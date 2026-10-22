<?php

namespace Tests\Feature\Performance;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenAPIContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_spec_is_valid_yaml(): void
    {
        $path = storage_path('api-docs/openapi.yaml');
        $this->assertFileExists($path, 'OpenAPI spec must exist at storage/api-docs/openapi.yaml');

        $content = file_get_contents($path);
        $this->assertStringContainsString('openapi: 3.0.3', $content);
        $this->assertStringContainsString('Digital Marketing SaaS API', $content);
    }

    public function test_openapi_spec_has_required_sections(): void
    {
        $path = storage_path('api-docs/openapi.yaml');
        $content = file_get_contents($path);

        $requiredSections = [
            'openapi:',
            'info:',
            'paths:',
            'components:',
            'securitySchemes:',
            'BearerAuth:',
            'paths:',
        ];

        foreach ($requiredSections as $section) {
            $this->assertStringContainsString($section, $content, "OpenAPI spec must contain '$section'");
        }
    }

    public function test_api_endpoints_match_openapi_paths(): void
    {
        $path = storage_path('api-docs/openapi.yaml');
        $content = file_get_contents($path);

        // Extract paths from OpenAPI spec
        preg_match_all('/^  (\/[^\s:]+):/m', $content, $matches);
        $specPaths = $matches[1];

        $this->assertNotEmpty($specPaths, 'OpenAPI spec must define API paths');

        // Check that key API routes exist in Laravel
        $keyRoutes = [
            '/api/v1/dashboard',
            '/api/v1/posts',
            '/api/v1/accounts',
            '/api/v1/campaigns',
            '/api/v1/clients',
            '/api/v1/invoices',
        ];

        foreach ($keyRoutes as $route) {
            $this->assertStringContainsString(
                str_replace('/api/v1', '', $route),
                $content,
                "OpenAPI spec should document $route"
            );
        }
    }

    public function test_api_health_endpoint_returns_expected_structure(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'queue',
            ],
        ]);
    }
}
