<?php

namespace Tests\Feature\Reporting;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class EnterpriseReportTest extends TestCase
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

    public function test_it_generates_a_custom_report(): void
    {
        Bus::fake();

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', [
            'name' => 'Test Social Report',
            'type' => 'social',
            'format' => 'pdf',
            'filters' => ['date_range' => 'last_30_days'],
            'columns' => ['total_posts', 'engagement_rate'],
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure(['message', 'report']);

        $this->assertDatabaseHas('reports', [
            'agency_id' => $this->agency->id,
            'name' => 'Test Social Report',
            'type' => 'social',
            'status' => 'pending',
        ]);
    }

    public function test_it_schedules_a_report(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/reports/schedule', [
            'name' => 'Weekly Analytics',
            'type' => 'analytics',
            'frequency' => 'weekly',
            'format' => 'csv',
            'filters' => ['date_range' => 'last_7_days'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'scheduled_report']);

        $this->assertDatabaseHas('scheduled_reports', [
            'agency_id' => $this->agency->id,
            'name' => 'Weekly Analytics',
            'frequency' => 'weekly',
            'is_active' => true,
        ]);
    }

    public function test_it_returns_report_types_and_metrics(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/types');

        $response->assertOk();
        $response->assertJsonStructure(['types', 'metrics']);
        $response->assertJsonFragment(['label' => 'Social Media']);
        $response->assertJsonFragment(['label' => 'Email Campaigns']);
    }
}
