<?php

namespace Tests\Feature\Feature;

use App\Models\Agency;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureTest extends TestCase
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

    public function test_it_lists_features(): void
    {
        Feature::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('features.flags.index'));

        $response->assertOk();
        $response->assertViewIs('features.index');
        $response->assertViewHas('features');
    }

    public function test_it_creates_a_feature(): void
    {
        $response = $this->actingAs($this->user)->post(route('features.flags.store'), [
            'code' => 'test_feature',
            'name' => 'Test Feature',
            'description' => 'Test description',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('features', ['code' => 'test_feature']);
    }

    public function test_it_validates_feature_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('features.flags.store'), []);

        $response->assertSessionHasErrors(['code', 'name']);
    }

    public function test_it_shows_a_feature(): void
    {
        $feature = Feature::factory()->create();

        $response = $this->actingAs($this->user)->get(route('features.flags.show', $feature));

        $response->assertOk();
        $response->assertViewIs('features.show');
    }

    public function test_it_edits_a_feature(): void
    {
        $feature = Feature::factory()->create();

        $response = $this->actingAs($this->user)->get(route('features.flags.edit', $feature));

        $response->assertOk();
        $response->assertViewIs('features.edit');
    }

    public function test_it_updates_a_feature(): void
    {
        $feature = Feature::factory()->create();

        $response = $this->actingAs($this->user)->put(route('features.flags.update', $feature), [
            'name' => 'Updated Feature',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('features', ['id' => $feature->id, 'name' => 'Updated Feature']);
    }

    public function test_it_deletes_a_feature(): void
    {
        $feature = Feature::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('features.flags.destroy', $feature));

        $response->assertRedirect(route('features.flags.index'));
        $this->assertDatabaseMissing('features', ['id' => $feature->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('features.flags.index'));

        $response->assertRedirect(route('login'));
    }
}
