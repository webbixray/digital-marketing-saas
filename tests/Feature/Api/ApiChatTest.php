<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiChatTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private Agency $otherAgency;
    private User $user;
    private User $otherAgencyUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->otherAgency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->otherAgencyUser = User::factory()->create(['agency_id' => $this->otherAgency->id]);
    }

    // ============================================================
    // channels()
    // ============================================================

    public function test_channels_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/chat/channels');
        $response->assertUnauthorized();
    }

    public function test_channels_returns_only_current_agency_channels(): void
    {
        $ownChannel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/chat/channels');

        $response->assertOk();
        $response->assertJsonCount(1, 'channels');
        $response->assertJsonPath('channels.0.id', $ownChannel->id);
        $response->assertJsonPath('channels.0.name', $ownChannel->name);
    }

    public function test_channels_excludes_archived(): void
    {
        ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
            'is_archived' => false,
        ]);
        ChatChannel::factory()->archived()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/chat/channels');

        $response->assertOk();
        $response->assertJsonCount(1, 'channels');
    }

    public function test_channels_returns_empty_list_when_no_channels(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/chat/channels');

        $response->assertOk();
        $response->assertJsonCount(0, 'channels');
    }

    // ============================================================
    // createChannel()
    // ============================================================

    public function test_create_channel_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/chat/channels', []);
        $response->assertUnauthorized();
    }

    public function test_create_channel_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'type', 'user_ids']);
    }

    public function test_create_channel_validates_type_enum(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels', [
            'name' => 'Test Channel',
            'type' => 'invalid_type',
            'user_ids' => [$this->user->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_create_channel_validates_user_ids_exist(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels', [
            'name' => 'Test Channel',
            'type' => 'public',
            'user_ids' => [99999],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_ids.0']);
    }

    public function test_create_channel_succeeds(): void
    {
        $otherUser = User::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels', [
            'name' => 'Marketing Team',
            'description' => 'Channel for marketing discussions',
            'type' => 'public',
            'user_ids' => [$otherUser->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('channel.name', 'Marketing Team');
        $response->assertJsonPath('channel.type', 'public');

        $this->assertDatabaseHas('chat_channels', [
            'name' => 'Marketing Team',
            'agency_id' => $this->agency->id,
            'type' => 'public',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_channel_attaches_creator_and_users(): void
    {
        $otherUser = User::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels', [
            'name' => 'Test Channel',
            'type' => 'private',
            'user_ids' => [$otherUser->id],
        ]);

        $response->assertCreated();
        $channel = ChatChannel::find($response->json('channel.id'));

        $this->assertNotNull($channel);
        $this->assertCount(2, $channel->users);
        $this->assertTrue($channel->users->contains($this->user));
        $this->assertTrue($channel->users->contains($otherUser));
    }

    // ============================================================
    // messages()
    // ============================================================

    public function test_messages_requires_auth(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/chat/channels/' . $channel->id . '/messages');
        $response->assertUnauthorized();
    }

    public function test_messages_returns_channel_messages(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/chat/channels/' . $channel->id . '/messages');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $message->id);
        $response->assertJsonPath('data.0.content', $message->content);
    }

    public function test_messages_prevents_cross_agency_access(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/chat/channels/' . $otherChannel->id . '/messages');

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    // ============================================================
    // sendMessage()
    // ============================================================

    public function test_send_message_requires_auth(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson('/api/v1/chat/channels/' . $channel->id . '/messages', []);
        $response->assertUnauthorized();
    }

    public function test_send_message_validates_content(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels/' . $channel->id . '/messages', [
            'content' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_send_message_succeeds(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels/' . $channel->id . '/messages', [
            'content' => 'Hello team!',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message.content', 'Hello team!');

        $this->assertDatabaseHas('chat_messages', [
            'content' => 'Hello team!',
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_send_message_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels/' . $otherChannel->id . '/messages', [
            'content' => 'Hacked!',
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    public function test_send_message_with_reply_to(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $originalMessage = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels/' . $channel->id . '/messages', [
            'content' => 'This is a reply',
            'reply_to_id' => $originalMessage->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('chat_messages', [
            'content' => 'This is a reply',
            'reply_to_id' => $originalMessage->id,
        ]);
    }

    // ============================================================
    // addReaction()
    // ============================================================

    public function test_add_reaction_requires_auth(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson('/api/v1/chat/messages/' . $message->id . '/reactions', []);
        $response->assertUnauthorized();
    }

    public function test_add_reaction_validates_emoji(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/messages/' . $message->id . '/reactions', [
            'emoji' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['emoji']);
    }

    public function test_add_reaction_succeeds(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/messages/' . $message->id . '/reactions', [
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

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/messages/' . $message->id . '/reactions', [
            'emoji' => '👍',
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    public function test_add_reaction_is_idempotent(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)->postJson('/api/v1/chat/messages/' . $message->id . '/reactions', ['emoji' => '👍']);
        $this->actingAs($this->user)->postJson('/api/v1/chat/messages/' . $message->id . '/reactions', ['emoji' => '👍']);

        $this->assertDatabaseCount('chat_reactions', 1);
    }

    // ============================================================
    // removeReaction()
    // ============================================================

    public function test_remove_reaction_requires_auth(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson('/api/v1/chat/messages/' . $message->id . '/reactions/👍');
        $response->assertUnauthorized();
    }

    public function test_remove_reaction_succeeds(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);
        ChatReaction::factory()->create([
            'message_id' => $message->id,
            'user_id' => $this->user->id,
            'emoji' => '👍',
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/chat/messages/' . $message->id . '/reactions/👍');

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

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/chat/messages/' . $message->id . '/reactions/👍');

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    public function test_remove_reaction_succeeds_even_if_not_exists(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/chat/messages/' . $message->id . '/reactions/👍');

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    // ============================================================
    // markAsRead()
    // ============================================================

    public function test_mark_as_read_requires_auth(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson('/api/v1/chat/channels/' . $channel->id . '/read');
        $response->assertUnauthorized();
    }

    public function test_mark_as_read_succeeds(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $channel->users()->attach($this->user->id);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels/' . $channel->id . '/read');

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $pivot = $channel->users()->where('user_id', $this->user->id)->first()->pivot;
        $this->assertNotNull($pivot->last_read_at);
    }

    public function test_mark_as_read_prevents_cross_agency(): void
    {
        $otherChannel = ChatChannel::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/chat/channels/' . $otherChannel->id . '/read');

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    // ============================================================
    // deleteMessage()
    // ============================================================

    public function test_delete_message_requires_auth(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson('/api/v1/chat/messages/' . $message->id);
        $response->assertUnauthorized();
    }

    public function test_delete_message_succeeds_for_own_message(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/chat/messages/' . $message->id);

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

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/chat/messages/' . $message->id);

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    public function test_delete_message_prevents_deleting_others_messages(): void
    {
        $otherUser = User::factory()->create(['agency_id' => $this->agency->id]);
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/chat/messages/' . $message->id);

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    // ============================================================
    // editMessage()
    // ============================================================

    public function test_edit_message_requires_auth(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson('/api/v1/chat/messages/' . $message->id, []);
        $response->assertUnauthorized();
    }

    public function test_edit_message_validates_content(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->putJson('/api/v1/chat/messages/' . $message->id, [
            'content' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_edit_message_succeeds_for_own_message(): void
    {
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
            'content' => 'Original',
            'is_edited' => false,
        ]);

        $response = $this->actingAs($this->user)->putJson('/api/v1/chat/messages/' . $message->id, [
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

        $response = $this->actingAs($this->user)->putJson('/api/v1/chat/messages/' . $message->id, [
            'content' => 'Hacked!',
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }

    public function test_edit_message_prevents_editing_others_messages(): void
    {
        $otherUser = User::factory()->create(['agency_id' => $this->agency->id]);
        $channel = ChatChannel::factory()->create([
            'agency_id' => $this->agency->id,
            'created_by' => $this->user->id,
        ]);
        $message = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->putJson('/api/v1/chat/messages/' . $message->id, [
            'content' => 'Trying to edit',
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Unauthorized');
    }
}
