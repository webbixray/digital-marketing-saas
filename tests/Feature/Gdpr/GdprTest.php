<?php

namespace Tests\Feature\Gdpr;

use App\Models\Agency;
use App\Models\ConsentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $agency->id]);
    }

    public function test_it_shows_gdpr_page(): void
    {
        ConsentRecord::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('gdpr.index'));

        $response->assertOk();
        $response->assertViewIs('gdpr.index');
        $response->assertViewHas('consents');
    }

    public function test_it_requests_export(): void
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

    public function test_it_validates_export_types(): void
    {
        $response = $this->actingAs($this->user)->post(route('gdpr.export'), []);

        $response->assertSessionHasErrors(['export_types']);
    }

    public function test_it_requests_deletion(): void
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

    public function test_it_updates_consent(): void
    {
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
    }

    public function test_it_validates_consent(): void
    {
        $response = $this->actingAs($this->user)->post(route('gdpr.consent'), []);

        $response->assertSessionHasErrors(['consent_type', 'granted']);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('gdpr.index'));

        $response->assertRedirect(route('login'));
    }
}
