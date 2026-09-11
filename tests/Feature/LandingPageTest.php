<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->actingAs($this->user);
    }

    public function test_it_lists_landing_pages(): void
    {
        LandingPage::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/landing-pages');
        $response->assertStatus(200);
        $response->assertViewIs('landing-pages.index');
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/landing-pages/create');
        $response->assertStatus(200);
        $response->assertViewIs('landing-pages.create');
    }

    public function test_it_creates_landing_page(): void
    {
        $data = ['name' => 'Test Landing Page', 'title' => 'Test Title'];
        $response = $this->post('/landing-pages', $data);
        $response->assertStatus(302);
        $response->assertRedirect('/landing-pages');
        $this->assertDatabaseHas('landing_pages', ['name' => 'Test Landing Page', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_landing_page_creation(): void
    {
        $response = $this->post('/landing-pages', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    public function test_it_prevents_accessing_other_agency_pages(): void
    {
        $otherAgency = Agency::factory()->create();
        $page = LandingPage::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->get("/landing-pages/{$page->id}/edit");
        $response->assertStatus(403);
    }

    public function test_it_shows_public_landing_page(): void
    {
        $page = LandingPage::factory()->create([
            'agency_id' => $this->agency->id,
            'is_published' => true,
        ]);
        $response = $this->get("/landing/{$page->slug}");
        $response->assertStatus(200);
    }

    public function test_public_render_requires_agency_ownership(): void
    {
        $otherAgency = Agency::factory()->create();
        $page = LandingPage::factory()->create([
            'agency_id' => $otherAgency->id,
            'is_published' => true,
        ]);
        $response = $this->get("/landing/{$page->slug}");
        $response->assertStatus(403);
    }

    public function test_public_render_works_for_published_page(): void
    {
        $page = LandingPage::factory()->create([
            'agency_id' => $this->agency->id,
            'is_published' => true,
        ]);
        $response = $this->get("/landing/{$page->slug}");
        $response->assertStatus(200);
    }
}
