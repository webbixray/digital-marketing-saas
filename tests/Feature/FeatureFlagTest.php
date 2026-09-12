<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->actingAs($this->user);
    }

    public function test_it_lists_feature_flags(): void
    {
        FeatureFlag::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/feature-flags');
        $response->assertStatus(200);
        $response->assertViewHas('flags');
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/feature-flags/create');
        $response->assertStatus(200);
    }

    public function test_it_creates_feature_flag(): void
    {
        $data = [
            'feature_key' => 'new_feature',
            'feature_name' => 'New Feature',
            'description' => 'A new feature',
            'enabled' => true,
        ];
        $response = $this->post('/feature-flags', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('feature_flags', ['feature_key' => 'new_feature', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->post('/feature-flags', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['feature_key', 'feature_name']);
    }

    public function test_it_prevents_duplicate_keys(): void
    {
        FeatureFlag::factory()->create(['agency_id' => $this->agency->id, 'feature_key' => 'dup_key']);
        $data = [
            'feature_key' => 'dup_key',
            'feature_name' => 'Duplicate',
        ];
        $response = $this->post('/feature-flags', $data);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['feature_key']);
    }

    public function test_it_shows_single_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->get("/feature-flags/{$flag->id}");
        $response->assertStatus(200);
        $response->assertViewHas('flag');
    }

    public function test_it_updates_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['agency_id' => $this->agency->id]);
        $data = [
            'feature_name' => 'Updated Name',
            'enabled' => false,
        ];
        $response = $this->put("/feature-flags/{$flag->id}", $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('feature_flags', ['id' => $flag->id, 'feature_name' => 'Updated Name']);
    }

    public function test_it_deletes_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->delete("/feature-flags/{$flag->id}");
        $response->assertStatus(302);
        $this->assertDatabaseMissing('feature_flags', ['id' => $flag->id]);
    }

    public function test_it_prevents_accessing_other_agency_flags(): void
    {
        $otherAgency = Agency::factory()->create();
        $flag = FeatureFlag::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->get("/feature-flags/{$flag->id}");
        $response->assertStatus(403);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/feature-flags');
        $response->assertRedirect('/login');
    }
}
