<?php

namespace Tests\Feature\CustomField;

use App\Models\Agency;
use App\Models\CustomField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldTest extends TestCase
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

    public function test_it_lists_fields(): void
    {
        CustomField::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('custom-fields.index'));

        $response->assertOk();
        $response->assertViewIs('custom-fields.index');
        $response->assertViewHas('fields');
    }

    public function test_it_creates_a_field(): void
    {
        $response = $this->actingAs($this->user)->post(route('custom-fields.store'), [
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'App\\Models\\Client',
            'is_required' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('custom_fields', [
            'name' => 'Test Field',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_validates_field_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('custom-fields.store'), []);

        $response->assertSessionHasErrors(['name', 'type', 'model_type']);
    }

    public function test_it_shows_a_field(): void
    {
        $field = CustomField::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('custom-fields.show', $field));

        $response->assertOk();
        $response->assertViewIs('custom-fields.show');
    }

    public function test_it_prevents_showing_other_agency_fields(): void
    {
        $otherAgency = Agency::factory()->create();
        $field = CustomField::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('custom-fields.show', $field));

        $response->assertForbidden();
    }

    public function test_it_edits_a_field(): void
    {
        $field = CustomField::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('custom-fields.edit', $field));

        $response->assertOk();
        $response->assertViewIs('custom-fields.edit');
    }

    public function test_it_updates_a_field(): void
    {
        $field = CustomField::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->put(route('custom-fields.update', $field), [
            'name' => 'Updated Field',
            'type' => 'number',
            'model_type' => 'App\\Models\\Client',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('custom_fields', [
            'id' => $field->id,
            'name' => 'Updated Field',
        ]);
    }

    public function test_it_deletes_a_field(): void
    {
        $field = CustomField::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->delete(route('custom-fields.destroy', $field));

        $response->assertRedirect(route('custom-fields.index'));
        $this->assertDatabaseMissing('custom_fields', ['id' => $field->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('custom-fields.index'));

        $response->assertRedirect(route('login'));
    }
}
