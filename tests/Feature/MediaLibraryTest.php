<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
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

    public function test_it_lists_media_assets(): void
    {
        MediaAsset::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/media');
        $response->assertStatus(200);
        $response->assertViewHas('assets');
    }

    public function test_it_filters_by_type(): void
    {
        MediaAsset::factory()->create(['agency_id' => $this->agency->id, 'file_type' => 'image']);
        MediaAsset::factory()->create(['agency_id' => $this->agency->id, 'file_type' => 'video']);
        $response = $this->get('/media?type=image');
        $response->assertStatus(200);
        $assets = $response->viewData('assets');
        $this->assertCount(1, $assets);
    }

    public function test_it_filters_by_folder(): void
    {
        MediaAsset::factory()->create(['agency_id' => $this->agency->id, 'folder' => 'brand']);
        MediaAsset::factory()->create(['agency_id' => $this->agency->id, 'folder' => 'products']);
        $response = $this->get('/media?folder=brand');
        $response->assertStatus(200);
        $assets = $response->viewData('assets');
        $this->assertCount(1, $assets);
    }

    public function test_it_searches_by_name(): void
    {
        MediaAsset::factory()->create(['agency_id' => $this->agency->id, 'name' => 'Logo']);
        MediaAsset::factory()->create(['agency_id' => $this->agency->id, 'name' => 'Banner']);
        $response = $this->get('/media?search=Logo');
        $response->assertStatus(200);
        $assets = $response->viewData('assets');
        $this->assertCount(1, $assets);
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/media/create');
        $response->assertStatus(200);
        $response->assertViewHas('folders');
    }

    public function test_it_prevents_accessing_other_agency_media(): void
    {
        $otherAgency = Agency::factory()->create();
        MediaAsset::factory()->count(3)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get('/media');
        $response->assertStatus(200);
        $assets = $response->viewData('assets');
        $this->assertCount(0, $assets);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/media');
        $response->assertRedirect('/login');
    }
}
