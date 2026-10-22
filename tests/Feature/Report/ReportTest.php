<?php

namespace Tests\Feature\Report;

use App\Models\Agency;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
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

    public function test_it_lists_reports(): void
    {
        Report::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('reports');
    }

    public function test_it_creates_a_report(): void
    {
        $response = $this->actingAs($this->user)->post(route('reports.store'), [
            'name' => 'Test Report',
            'type' => 'social',
            'format' => 'pdf',
            'schedule' => 'once',
        ]);

        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseHas('reports', [
            'name' => 'Test Report',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_validates_report_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('reports.store'), []);

        $response->assertSessionHasErrors(['name', 'type', 'format', 'schedule']);
    }

    public function test_it_shows_a_report(): void
    {
        $report = Report::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertViewIs('reports.show');
        $response->assertViewHas('report');
    }

    public function test_it_prevents_showing_other_agency_reports(): void
    {
        $otherAgency = Agency::factory()->create();
        $report = Report::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('reports.show', $report));

        $response->assertForbidden();
    }

    public function test_it_deletes_a_report(): void
    {
        $report = Report::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->delete(route('reports.destroy', $report));

        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_it_generates_a_report(): void
    {
        $report = Report::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->post(route('reports.generate', $report));

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'processing',
        ]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }
}
