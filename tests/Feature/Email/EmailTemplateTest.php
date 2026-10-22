<?php

namespace Tests\Feature\Email;

use App\Models\Agency;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
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
        EmailTemplate::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('email.templates.index'));

        $response->assertOk();
        $response->assertViewIs('email.templates.index');
        $response->assertViewHas('templates');
    }

    public function test_it_creates_a_template(): void
    {
        $response = $this->actingAs($this->user)->post(route('email.templates.store'), [
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'category' => 'general',
            'html_content' => '<p>Test content</p>',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_templates', [
            'name' => 'Test Template',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_validates_template_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('email.templates.store'), []);

        $response->assertSessionHasErrors(['name', 'subject', 'category', 'html_content']);
    }

    public function test_it_shows_a_template(): void
    {
        $template = EmailTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('email.templates.show', $template));

        $response->assertOk();
        $response->assertViewIs('email.templates.show');
    }

    public function test_it_prevents_showing_other_agency_templates(): void
    {
        $otherAgency = Agency::factory()->create();
        $template = EmailTemplate::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('email.templates.show', $template));

        $response->assertForbidden();
    }

    public function test_it_edits_a_template(): void
    {
        $template = EmailTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('email.templates.edit', $template));

        $response->assertOk();
        $response->assertViewIs('email.templates.edit');
    }

    public function test_it_updates_a_template(): void
    {
        $template = EmailTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->put(route('email.templates.update', $template), [
            'name' => 'Updated Template',
            'subject' => 'Updated Subject',
            'category' => 'general',
            'html_content' => '<p>Updated content</p>',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Updated Template',
        ]);
    }

    public function test_it_deletes_a_template(): void
    {
        $template = EmailTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->delete(route('email.templates.destroy', $template));

        $response->assertRedirect(route('email.templates.index'));
        $this->assertSoftDeleted('email_templates', ['id' => $template->id]);
    }

    public function test_it_previews_a_template(): void
    {
        $template = EmailTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('email.templates.preview', $template));

        $response->assertOk();
        $response->assertJson(['html' => $template->html_content]);
    }

    public function test_it_duplicates_a_template(): void
    {
        $template = EmailTemplate::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('email.templates.duplicate', $template));

        $response->assertRedirect();
        $this->assertDatabaseHas('email_templates', [
            'name' => $template->name.' (Copy)',
        ]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('email.templates.index'));

        $response->assertRedirect(route('login'));
    }
}
