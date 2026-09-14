<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_cross_platform_analytics_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/analytics/cross-platform');
        $response->assertStatus(401);
    }

    public function test_cross_platform_analytics_returns_all_platforms(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analytics/cross-platform');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube',
                ],
            ]);
    }

    public function test_best_performing_platform(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analytics/best-platform');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_optimal_posting_times(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analytics/optimal-times');
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }
}
