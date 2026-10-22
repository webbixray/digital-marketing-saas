<?php

namespace Tests\Feature\FeatureFlag;

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
    }

    public function test_it_lists_flags(): void
    {
        FeatureFlag::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('feature-flags.index'));

        $response->assertOk();
        $response->assertViewIs('feature-flags.index');
        $response->assertViewHas('flags');
    }

    public function test_it_creates_a_flag(): void
    {
        $response = $this->actingAs($this->user)->post(route('feature-flags.store'), [
            'feature_key' => 'test_flag',
            'feature_name' => 'Test Flag',
            'enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feature_flags', ['feature_key' => 'test_flag']);
    }

    public function test_it_validates_flag_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('feature-flags.store'), []);

        $response->assertSessionHasErrors(['feature_key', 'feature_name']);
    }

    public function test_it_shows_a_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('feature-flags.show', $flag));

        $response->assertOk();
        $response->assertViewIs('feature-flags.show');
    }

    public function test_it_prevents_showing_other_agency_flags(): void
    {
        $otherAgency = Agency::factory()->create();
        $flag = FeatureFlag::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('feature-flags.show', $flag));

        $response->assertForbidden();
    }

    public function test_it_edits_a_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('feature-flags.edit', $flag));

        $response->assertOk();
        $response->assertViewIs('feature-flags.edit');
    }

    public function test_it_updates_a_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->put(route('feature-flags.update', $flag), [
            'feature_name' => 'Updated Flag',
            'enabled' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feature_flags', ['id' => $flag->id, 'feature_name' => 'Updated Flag']);
    }

    public function test_it_deletes_a_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->delete(route('feature-flags.destroy', $flag));

        $response->assertRedirect(route('feature-flags.index'));
        $this->assertDatabaseMissing('feature_flags', ['id' => $flag->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('feature-flags.index'));

        $response->assertRedirect(route('login'));
    }
}
