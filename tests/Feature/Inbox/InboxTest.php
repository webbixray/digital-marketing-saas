<?php

namespace Tests\Feature\Inbox;

use App\Models\Agency;
use App\Models\InboxMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxTest extends TestCase
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

    public function test_it_lists_messages(): void
    {
        InboxMessage::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('unified-inbox.index'));

        $response->assertOk();
        $response->assertViewIs('inbox.index');
        $response->assertViewHas('inbox');
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('unified-inbox.index'));

        $response->assertRedirect(route('login'));
    }
}
