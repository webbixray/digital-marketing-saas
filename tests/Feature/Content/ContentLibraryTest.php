<?php

namespace Tests\Feature\Content;

use App\Models\Agency;
use App\Models\ContentAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentLibraryTest extends TestCase
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

    public function test_it_lists_assets(): void
    {
        ContentAsset::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('content.index'));

        $response->assertOk();
        $response->assertViewIs('content.index');
        $response->assertViewHas('assets');
    }

    public function test_it_creates_an_asset(): void
    {
        $response = $this->actingAs($this->user)->post(route('content.store'), [
            'name' => 'Test Asset',
            'type' => 'text',
            'content' => 'Test content',
            'tags' => ['test'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('content_assets', [
            'name' => 'Test Asset',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_validates_asset_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('content.store'), []);

        $response->assertSessionHasErrors(['name', 'type', 'content']);
    }

    public function test_it_shows_an_asset(): void
    {
        $asset = ContentAsset::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('content.show', $asset));

        $response->assertOk();
        $response->assertViewIs('content.show');
    }

    public function test_it_prevents_showing_other_agency_assets(): void
    {
        $otherAgency = Agency::factory()->create();
        $asset = ContentAsset::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('content.show', $asset));

        $response->assertForbidden();
    }

    public function test_it_edits_an_asset(): void
    {
        $asset = ContentAsset::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('content.edit', $asset));

        $response->assertOk();
        $response->assertViewIs('content.edit');
    }

    public function test_it_updates_an_asset(): void
    {
        $asset = ContentAsset::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->put(route('content.update', $asset), [
            'name' => 'Updated Asset',
            'content' => 'Updated content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('content_assets', [
            'id' => $asset->id,
            'name' => 'Updated Asset',
        ]);
    }

    public function test_it_deletes_an_asset(): void
    {
        $asset = ContentAsset::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->delete(route('content.destroy', $asset));

        $response->assertRedirect(route('content.index'));
        $this->assertSoftDeleted('content_assets', ['id' => $asset->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('content.index'));

        $response->assertRedirect(route('login'));
    }
}
