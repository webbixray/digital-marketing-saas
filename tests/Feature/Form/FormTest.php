<?php

namespace Tests\Feature\Form;

use App\Models\Agency;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormTest extends TestCase
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

    public function test_it_lists_forms(): void
    {
        Form::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('forms.index'));

        $response->assertOk();
        $response->assertViewIs('forms.index');
        $response->assertViewHas('forms');
    }

    public function test_it_creates_a_form(): void
    {
        $response = $this->actingAs($this->user)->post(route('forms.store'), [
            'name' => 'Test Form',
            'fields' => [
                ['name' => 'email', 'type' => 'email', 'required' => true],
                ['name' => 'message', 'type' => 'textarea', 'required' => false],
            ],
            'success_message' => 'Thank you!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('forms', [
            'name' => 'Test Form',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_validates_form_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('forms.store'), []);

        $response->assertSessionHasErrors(['name', 'fields']);
    }

    public function test_it_shows_a_form(): void
    {
        $form = Form::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('forms.show', $form));

        $response->assertOk();
        $response->assertViewIs('forms.show');
        $response->assertViewHas('form');
    }

    public function test_it_prevents_showing_other_agency_forms(): void
    {
        $otherAgency = Agency::factory()->create();
        $form = Form::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('forms.show', $form));

        $response->assertForbidden();
    }

    public function test_it_edits_a_form(): void
    {
        $form = Form::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('forms.edit', $form));

        $response->assertOk();
        $response->assertViewIs('forms.edit');
    }

    public function test_it_updates_a_form(): void
    {
        $form = Form::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->put(route('forms.update', $form), [
            'name' => 'Updated Form',
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'required' => true],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'name' => 'Updated Form',
        ]);
    }

    public function test_it_deletes_a_form(): void
    {
        $form = Form::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->delete(route('forms.destroy', $form));

        $response->assertRedirect(route('forms.index'));
        $this->assertSoftDeleted('forms', ['id' => $form->id]);
    }

    public function test_it_toggles_form_publish(): void
    {
        $form = Form::factory()->create([
            'agency_id' => $this->agency->id,
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->user)->post(route('forms.toggle', $form));

        $response->assertRedirect(route('forms.index'));
        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'is_published' => true,
        ]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('forms.index'));

        $response->assertRedirect(route('login'));
    }
}
