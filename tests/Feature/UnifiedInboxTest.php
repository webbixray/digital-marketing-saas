<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\InboxMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedInboxTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_inbox_index_requires_auth(): void
    {
        $response = $this->get(route('unified-inbox.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_inbox_index_loads(): void
    {
        InboxMessage::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('unified-inbox.index'));
        $response->assertStatus(200);
        $response->assertViewIs('inbox.index');
    }

    public function test_inbox_show_marks_as_read(): void
    {
        $message = InboxMessage::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'unread',
        ]);

        $response = $this->actingAs($this->user)->get(route('unified-inbox.show', $message->id));
        $response->assertStatus(200);

        $this->assertDatabaseHas('inbox_messages', [
            'id' => $message->id,
            'status' => 'read',
        ]);
    }

    public function test_api_mark_read(): void
    {
        $message = InboxMessage::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'unread',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.unified-inbox.mark-read', $message->id));

        $response->assertStatus(200);
        $this->assertDatabaseHas('inbox_messages', [
            'id' => $message->id,
            'status' => 'read',
        ]);
    }

    public function test_api_mark_all_read(): void
    {
        InboxMessage::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'unread',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.unified-inbox.mark-all-read'));

        $response->assertStatus(200)
            ->assertJsonPath('count', 3);
    }

    public function test_api_delete(): void
    {
        $message = InboxMessage::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('api.unified-inbox.delete', $message->id));

        $response->assertStatus(200);
        $this->assertSoftDeleted('inbox_messages', ['id' => $message->id]);
    }
}
