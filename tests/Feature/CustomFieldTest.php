<?php

namespace Tests\Feature;

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
        $this->actingAs($this->user);
    }

    public function test_it_lists_custom_fields(): void
    {
        CustomField::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/custom-fields');
        $response->assertStatus(200);
        $response->assertViewHas('fields');
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/custom-fields/create');
        $response->assertStatus(200);
        $response->assertViewHas('types');
    }

    public function test_it_creates_custom_field(): void
    {
        $data = [
            'name' => 'Priority',
            'type' => 'select',
            'model_type' => 'App\\Models\\Client',
            'options' => ['High', 'Medium', 'Low'],
        ];
        $response = $this->post('/custom-fields', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('custom_fields', ['name' => 'Priority', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->post('/custom-fields', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name', 'type', 'model_type']);
    }

    public function test_it_prevents_accessing_other_agency_fields(): void
    {
        $otherAgency = Agency::factory()->create();
        CustomField::factory()->count(3)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get('/custom-fields');
        $response->assertStatus(200);
        $fields = $response->viewData('fields');
        $this->assertCount(0, $fields);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/custom-fields');
        $response->assertRedirect('/login');
    }
}
