<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\ContentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTemplateTest extends TestCase
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

    public function test_it_lists_templates(): void
    {
        ContentTemplate::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/content-templates');
        $response->assertStatus(200);
        $response->assertViewHas('templates');
    }

    public function test_it_filters_by_platform(): void
    {
        ContentTemplate::factory()->create(['agency_id' => $this->agency->id, 'platform' => 'twitter']);
        ContentTemplate::factory()->create(['agency_id' => $this->agency->id, 'platform' => 'facebook']);
        $response = $this->get('/content-templates?platform=twitter');
        $response->assertStatus(200);
        $templates = $response->viewData('templates');
        $this->assertCount(1, $templates);
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/content-templates/create');
        $response->assertStatus(200);
        $response->assertViewHas('platforms');
    }

    public function test_it_creates_template(): void
    {
        $data = [
            'name' => 'Test Template',
            'platform' => 'twitter',
            'type' => 'post',
            'template_content' => 'Hello World',
            'status' => 'active',
        ];
        $response = $this->post('/content-templates', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('content_templates', ['name' => 'Test Template', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->post('/content-templates', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name', 'platform', 'type', 'template_content', 'status']);
    }

    public function test_it_prevents_accessing_other_agency_templates(): void
    {
        $otherAgency = Agency::factory()->create();
        ContentTemplate::factory()->count(3)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get('/content-templates');
        $response->assertStatus(200);
        $templates = $response->viewData('templates');
        $this->assertCount(0, $templates);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/content-templates');
        $response->assertRedirect('/login');
    }
}
