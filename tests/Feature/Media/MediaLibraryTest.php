<?php

namespace Tests\Feature\Media;

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
    }

    public function test_it_lists_assets(): void
    {
        MediaAsset::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('media.index'));

        $response->assertOk();
        $response->assertViewIs('media.index');
        $response->assertViewHas('assets');
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('media.create'));

        $response->assertOk();
        $response->assertViewIs('media.create');
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('media.index'));

        $response->assertRedirect(route('login'));
    }
}
