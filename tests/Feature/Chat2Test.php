<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Chat2Test extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private Agency $otherAgency;
    private User $user;
    private User $teamMember;
    private User $otherAgencyUser;
    private ChatChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->otherAgency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->teamMember = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->otherAgencyUser = User::factory()->create(['agency_id' => $this->otherAgency->id]);
        
        $this->channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
            'type' => 'public',
        ]);
        $this->channel->users()->attach([$this->user->id, $this->teamMember->id]);
    }

    // ============================================================
    // index()
    // ============================================================

    public function test_index_requires_auth(): void
    {
        $response = $this->get('/chat/v2');
        $response->assertStatus(302); // Redirect to login
    }

    public function test_index_requires_agency(): void
    {
        $userWithoutAgency = User::factory()->create(['agency_id' => null]);
        $response = $this->actingAs($userWithoutAgency)->get('/chat/v2');
        $response->assertStatus(403); // Forbidden (no agency assigned)
    }

    public function test_index_returns_200_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/v2');
        $response->assertStatus(200);
        $response->assertViewIs('chat.index');
    }

    public function test_index_returns_channels_for_current_agency(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/v2');
        $response->assertViewHas('channels');
        $channels = $response->viewData('channels');
        $this->assertCount(1, $channels);
        $this->assertEquals($this->channel->id, $channels->first()->id);
    }

    public function test_index_does_not_return_other_agency_channels(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->get('/chat/v2');
        $channels = $response->viewData('channels');
        $this->assertCount(1, $channels);
        $this->assertFalse($channels->contains('id', $otherChannel->id));
    }

    public function test_index_sets_first_channel_as_active(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/v2');
        $response->assertViewHas('activeChannel');
        $activeChannel = $response->viewData('activeChannel');
        $this->assertNotNull($activeChannel);
        $this->assertEquals($this->channel->id, $activeChannel->id);
    }

    public function test_index_loads_messages_for_active_channel(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/chat/v2');
        $response->assertViewHas('messages');
    }

    // ============================================================
    // channel()
    // ============================================================

    public function test_channel_requires_auth(): void
    {
        $response = $this->get('/chat/v2/' . $this->channel->id);
        $response->assertStatus(302);
    }

    public function test_channel_returns_200_for_valid_channel(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/v2/' . $this->channel->id);
        $response->assertStatus(200);
        $response->assertViewIs('chat.channel');
    }

    public function test_channel_prevents_cross_agency_access(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->get('/chat/v2/' . $otherChannel->id);
        $response->assertStatus(403);
    }

    public function test_channel_loads_messages(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/chat/v2/' . $this->channel->id);
        $response->assertViewHas('messages');
    }

    public function test_channel_marks_as_read_on_view(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/v2/' . $this->channel->id);
        $response->assertStatus(200);

        $this->assertDatabaseHas('chat_channel_user', [
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);
        
        $pivot = $this->channel->users()->where('user_id', $this->user->id)->first()->pivot;
        $this->assertNotNull($pivot->last_read_at);
    }

    // ============================================================
    // sendMessage()
    // ============================================================

    public function test_send_message_requires_auth(): void
    {
        $response = $this->postJson('/chat/v2/' . $this->channel->id . '/send', [
            'content' => 'Hello!',
        ]);
        $response->assertUnauthorized();
    }

    public function test_send_message_validates_content(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $this->channel->id . '/send', [
            'content' => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_send_message_succeeds(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $this->channel->id . '/send', [
            'content' => 'Hello team!',
        ]);
        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message.content', 'Hello team!');

        $this->assertDatabaseHas('chat_messages', [
            'content' => 'Hello team!',
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_send_message_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $otherChannel->id . '/send', [
            'content' => 'Hacked!',
        ]);
        $response->assertForbidden();
    }

    public function test_send_message_with_reply_to(): void
    {
        $original = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->teamMember)->postJson('/chat/v2/' . $this->channel->id . '/send', [
            'content' => 'This is a reply',
            'reply_to_id' => $original->id,
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('chat_messages', [
            'content' => 'This is a reply',
            'reply_to_id' => $original->id,
        ]);
    }

    public function test_send_message_with_file_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $this->channel->id . '/send', [
            'content' => 'Check this file',
            'file_url' => 'https://example.com/file.pdf',
            'file_name' => 'file.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('chat_messages', [
            'content' => 'Check this file',
            'file_name' => 'file.pdf',
            'type' => 'file',
        ]);
    }

    // ============================================================
    // typing()
    // ============================================================

    public function test_typing_requires_auth(): void
    {
        $response = $this->postJson('/chat/v2/' . $this->channel->id . '/typing', [
            'typing' => true,
        ]);
        $response->assertUnauthorized();
    }

    public function test_typing_succeeds(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $this->channel->id . '/typing', [
            'typing' => true,
        ]);
        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_typing_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $otherChannel->id . '/typing', [
            'typing' => true,
        ]);
        $response->assertForbidden();
    }

    // ============================================================
    // read()
    // ============================================================

    public function test_read_requires_auth(): void
    {
        $response = $this->postJson('/chat/v2/' . $this->channel->id . '/read');
        $response->assertUnauthorized();
    }

    public function test_read_succeeds(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $this->channel->id . '/read');
        $response->assertOk();
        $response->assertJsonPath('success', true);

        $pivot = $this->channel->users()->where('user_id', $this->user->id)->first()->pivot;
        $this->assertNotNull($pivot->last_read_at);
    }

    public function test_read_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $otherChannel->id . '/read');
        $response->assertForbidden();
    }

    // ============================================================
    // addReaction()
    // ============================================================

    public function test_add_reaction_requires_auth(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson('/chat/v2/messages/' . $message->id . '/reactions', [
            'emoji' => '👍',
        ]);
        $response->assertUnauthorized();
    }

    public function test_add_reaction_validates_emoji(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/chat/v2/messages/' . $message->id . '/reactions', [
            'emoji' => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['emoji']);
    }

    public function test_add_reaction_succeeds(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/chat/v2/messages/' . $message->id . '/reactions', [
            'emoji' => '👍',
        ]);
        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('chat_reactions', [
            'message_id' => $message->id,
            'user_id' => $this->user->id,
            'emoji' => '👍',
        ]);
    }

    public function test_add_reaction_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $otherChannel->id,
            'user_id' => $this->otherAgencyUser->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/chat/v2/messages/' . $message->id . '/reactions', [
            'emoji' => '👍',
        ]);
        $response->assertForbidden();
    }

    public function test_add_reaction_is_idempotent(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)->postJson('/chat/v2/messages/' . $message->id . '/reactions', ['emoji' => '👍']);
        $this->actingAs($this->user)->postJson('/chat/v2/messages/' . $message->id . '/reactions', ['emoji' => '👍']);

        $this->assertDatabaseCount('chat_reactions', 1);
    }

    // ============================================================
    // removeReaction()
    // ============================================================

    public function test_remove_reaction_requires_auth(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson('/chat/v2/messages/' . $message->id . '/reactions/' . urlencode('👍'));
        $response->assertUnauthorized();
    }

    public function test_remove_reaction_succeeds(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);
        ChatReaction::factory()->create([
            'message_id' => $message->id,
            'user_id' => $this->user->id,
            'emoji' => '👍',
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/chat/v2/messages/' . $message->id . '/reactions/' . urlencode('👍'));
        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('chat_reactions', [
            'message_id' => $message->id,
            'user_id' => $this->user->id,
            'emoji' => '👍',
        ]);
    }

    public function test_remove_reaction_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $otherChannel->id,
            'user_id' => $this->otherAgencyUser->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/chat/v2/messages/' . $message->id . '/reactions/' . urlencode('👍'));
        $response->assertForbidden();
    }

    public function test_remove_reaction_succeeds_even_if_not_exists(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/chat/v2/messages/' . $message->id . '/reactions/' . urlencode('👍'));
        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    // ============================================================
    // editMessage()
    // ============================================================

    public function test_edit_message_requires_auth(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson('/chat/v2/messages/' . $message->id, [
            'content' => 'Updated',
        ]);
        $response->assertUnauthorized();
    }

    public function test_edit_message_validates_content(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->putJson('/chat/v2/messages/' . $message->id, [
            'content' => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_edit_message_succeeds_for_own_message(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
            'content' => 'Original',
        ]);

        $response = $this->actingAs($this->user)->putJson('/chat/v2/messages/' . $message->id, [
            'content' => 'Updated content',
        ]);
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message.content', 'Updated content');

        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'content' => 'Updated content',
            'is_edited' => true,
        ]);
    }

    public function test_edit_message_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $otherChannel->id,
            'user_id' => $this->otherAgencyUser->id,
        ]);

        $response = $this->actingAs($this->user)->putJson('/chat/v2/messages/' . $message->id, [
            'content' => 'Hacked!',
        ]);
        $response->assertForbidden();
    }

    public function test_edit_message_prevents_editing_others_messages(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->teamMember->id,
        ]);

        $response = $this->actingAs($this->user)->putJson('/chat/v2/messages/' . $message->id, [
            'content' => 'Trying to edit',
        ]);
        $response->assertForbidden();
    }

    // ============================================================
    // deleteMessage()
    // ============================================================

    public function test_delete_message_requires_auth(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson('/chat/v2/messages/' . $message->id);
        $response->assertUnauthorized();
    }

    public function test_delete_message_succeeds_for_own_message(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/chat/v2/messages/' . $message->id);
        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'is_deleted' => true,
            'content' => 'This message was deleted',
        ]);
    }

    public function test_delete_message_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $otherChannel->id,
            'user_id' => $this->otherAgencyUser->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/chat/v2/messages/' . $message->id);
        $response->assertForbidden();
    }

    public function test_delete_message_prevents_deleting_others_messages(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->teamMember->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/chat/v2/messages/' . $message->id);
        $response->assertForbidden();
    }

    // ============================================================
    // Security & Edge Cases
    // ============================================================

    public function test_send_message_max_length_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/v2/' . $this->channel->id . '/send', [
            'content' => str_repeat('a', 5001),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_channel_shows_typing_indicator_state(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/v2/' . $this->channel->id);
        $response->assertStatus(200);
        // The view includes typing indicator markup
        $response->assertSee('typing-indicator');
    }

    public function test_reactions_summary_displayed_in_channel(): void
    {
        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'user_id' => $this->user->id,
        ]);
        ChatReaction::factory()->create([
            'message_id' => $message->id,
            'user_id' => $this->teamMember->id,
            'emoji' => '👍',
        ]);

        $response = $this->actingAs($this->user)->get('/chat/v2/' . $this->channel->id);
        $response->assertStatus(200);
        $response->assertSee('👍');
    }
}
