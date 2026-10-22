<?php

namespace Tests\Feature\GDPR;

use App\Models\Agency;
use App\Models\ConsentRecord;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GDPRComplianceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_it_records_and_withdraws_consent(): void
    {
        // Record consent
        $response = $this->actingAs($this->user)->post(route('gdpr.consent'), [
            'consent_type' => 'marketing',
            'granted' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('consent_records', [
            'user_id' => $this->user->id,
            'consent_type' => 'marketing',
            'granted' => true,
        ]);

        // Withdraw consent
        $response = $this->actingAs($this->user)->post(route('gdpr.consent'), [
            'consent_type' => 'marketing',
            'granted' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('consent_records', [
            'user_id' => $this->user->id,
            'consent_type' => 'marketing',
            'granted' => false,
        ]);
    }

    public function test_it_creates_data_export_request(): void
    {
        $response = $this->actingAs($this->user)->post(route('gdpr.export'), [
            'export_types' => ['posts', 'campaigns'],
        ]);

        $response->assertRedirect(route('gdpr.index'));
        $this->assertDatabaseHas('data_export_requests', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_it_creates_data_deletion_request(): void
    {
        $response = $this->actingAs($this->user)->post(route('gdpr.delete'), [
            'reason' => 'No longer needed',
        ]);

        $response->assertRedirect(route('gdpr.index'));
        $this->assertDatabaseHas('data_deletion_requests', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_it_shows_gdpr_page_with_consent_history(): void
    {
        ConsentRecord::factory()->count(2)->create(['user_id' => $this->user->id]);
        DataExportRequest::factory()->create(['user_id' => $this->user->id]);
        DataDeletionRequest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('gdpr.index'));

        $response->assertOk();
        $response->assertViewIs('gdpr.index');
        $response->assertViewHas('consents');
        $response->assertViewHas('exportRequests');
        $response->assertViewHas('deletionRequests');
    }
}
