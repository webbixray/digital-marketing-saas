<?php

namespace Tests\Feature\Workflow;

use App\Models\Agency;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
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

    public function test_it_lists_workflows(): void
    {
        Workflow::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('workflows.index'));

        $response->assertOk();
        $response->assertViewIs('workflows.index');
        $response->assertViewHas('workflows');
    }

    public function test_it_shows_a_workflow(): void
    {
        $workflow = Workflow::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('workflows.show', $workflow));

        $response->assertOk();
        $response->assertViewIs('workflows.show');
        $response->assertViewHas('workflow');
    }

    public function test_it_prevents_showing_other_agency_workflows(): void
    {
        $otherAgency = Agency::factory()->create();
        $workflow = Workflow::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('workflows.show', $workflow));

        $response->assertForbidden();
    }

    public function test_it_edits_a_workflow(): void
    {
        $workflow = Workflow::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('workflows.edit', $workflow));

        $response->assertOk();
        $response->assertViewIs('workflows.edit');
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('workflows.index'));

        $response->assertRedirect(route('login'));
    }
}
