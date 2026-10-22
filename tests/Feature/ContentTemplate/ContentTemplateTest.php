<?php

namespace Tests\Feature\ContentTemplate;

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
    }

    public function test_it_lists_templates(): void
    {
        ContentTemplate::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('content-templates.index'));

        $response->assertOk();
        $response->assertViewIs('content-templates.index');
        $response->assertViewHas('templates');
    }

    public function test_it_creates_a_template(): void
    {
        $response = $this->actingAs($this->user)->post(route('content-templates.store'), [
            'name' => 'Test Template',
            'platform' => 'twitter',
            'type' => 'post',
            'template_content' => 'Hello {{name}}!',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('content_templates', [
            'name' => 'Test Template',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_validates_template_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('content-templates.store'), []);

        $response->assertSessionHasErrors(['name', 'platform', 'type', 'template_content', 'status']);
    }

    public function test_it_shows_a_template(): void
    {
        $template = ContentTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('content-templates.show', $template));

        $response->assertOk();
        $response->assertViewIs('content-templates.show');
    }

    public function test_it_prevents_showing_other_agency_templates(): void
    {
        $otherAgency = Agency::factory()->create();
        $template = ContentTemplate::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('content-templates.show', $template));

        $response->assertForbidden();
    }

    public function test_it_edits_a_template(): void
    {
        $template = ContentTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('content-templates.edit', $template));

        $response->assertOk();
        $response->assertViewIs('content-templates.edit');
    }

    public function test_it_updates_a_template(): void
    {
        $template = ContentTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->put(route('content-templates.update', $template), [
            'name' => 'Updated Template',
            'platform' => 'facebook',
            'type' => 'story',
            'template_content' => 'Updated content',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('content_templates', [
            'id' => $template->id,
            'name' => 'Updated Template',
        ]);
    }

    public function test_it_deletes_a_template(): void
    {
        $template = ContentTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->delete(route('content-templates.destroy', $template));

        $response->assertRedirect(route('content-templates.index'));
        $this->assertDatabaseMissing('content_templates', ['id' => $template->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('content-templates.index'));

        $response->assertRedirect(route('login'));
    }
}
