<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatChannelModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Basic Creation & Fillable
    // =========================================================================

    public function test_chat_channel_can_be_created_with_factory(): void
    {
        $channel = ChatChannel::factory()->create();

        $this->assertDatabaseHas('chat_channels', [
            'id' => $channel->id,
            'name' => $channel->name,
        ]);
    }

    public function test_chat_channel_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create();

        $channel = ChatChannel::create([
            'agency_id' => $agency->id,
            'name' => 'General Discussion',
            'slug' => 'general-discussion',
            'description' => 'Main channel',
            'type' => 'public',
            'created_by' => $user->id,
            'is_archived' => false,
        ]);

        $this->assertNotNull($channel);
        $this->assertEquals('General Discussion', $channel->name);
        $this->assertEquals('public', $channel->type);
        $this->assertFalse($channel->is_archived);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function test_chat_channel_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $channel->agency);
        $this->assertEquals($agency->id, $channel->agency->id);
    }

    public function test_chat_channel_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $channel = ChatChannel::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $channel->creator);
        $this->assertEquals($user->id, $channel->creator->id);
    }

    public function test_chat_channel_has_many_messages(): void
    {
        $channel = ChatChannel::factory()->create();
        ChatMessage::factory()->count(5)->create(['channel_id' => $channel->id]);

        $this->assertCount(5, $channel->messages);
        $this->assertInstanceOf(ChatMessage::class, $channel->messages->first());
    }

    public function test_chat_channel_belongs_to_many_users(): void
    {
        $channel = ChatChannel::factory()->create();
        $user = User::factory()->create();
        $channel->users()->attach($user->id);

        $this->assertCount(1, $channel->users);
        $this->assertInstanceOf(User::class, $channel->users->first());
    }

    public function test_chat_channel_users_have_pivot_data(): void
    {
        $channel = ChatChannel::factory()->create();
        $user = User::factory()->create();
        $channel->users()->attach($user->id, [
            'last_read_at' => now(),
            'is_moderator' => true,
        ]);

        $pivotUser = $channel->users->first();
        $this->assertNotNull($pivotUser->pivot->last_read_at);
        $this->assertEquals(1, $pivotUser->pivot->is_moderator);
    }

    // =========================================================================
    // Casts
    // =========================================================================

    public function test_is_archived_casts_to_boolean(): void
    {
        $channel = ChatChannel::factory()->create(['is_archived' => 1]);

        $this->assertIsBool($channel->is_archived);
        $this->assertTrue($channel->is_archived);
    }

    public function test_is_archived_false_casts_to_boolean(): void
    {
        $channel = ChatChannel::factory()->create(['is_archived' => 0]);

        $this->assertIsBool($channel->is_archived);
        $this->assertFalse($channel->is_archived);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function test_scope_active_excludes_archived_channels(): void
    {
        ChatChannel::factory()->create(['is_archived' => false]);
        ChatChannel::factory()->create(['is_archived' => true]);

        $channels = ChatChannel::active()->get();

        $this->assertCount(1, $channels);
        $this->assertFalse($channels->first()->is_archived);
    }

    public function test_scope_public_filters_by_public_type(): void
    {
        ChatChannel::factory()->create(['type' => 'public']);
        ChatChannel::factory()->create(['type' => 'private']);

        $channels = ChatChannel::public()->get();

        $this->assertCount(1, $channels);
        $this->assertEquals('public', $channels->first()->type);
    }

    public function test_scope_for_user_filters_by_membership(): void
    {
        $user = User::factory()->create();
        $channel1 = ChatChannel::factory()->create();
        $channel2 = ChatChannel::factory()->create();

        $channel1->users()->attach($user->id);

        $channels = ChatChannel::forUser($user)->get();

        $this->assertCount(1, $channels);
        $this->assertEquals($channel1->id, $channels->first()->id);
    }

    public function test_scope_for_agency_from_trait_filters_correctly(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        ChatChannel::factory()->create(['agency_id' => $agency1->id]);
        ChatChannel::factory()->create(['agency_id' => $agency2->id]);

        $results = ChatChannel::forAgency($agency1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($agency1->id, $results->first()->agency_id);
    }

    // =========================================================================
    // Factory States
    // =========================================================================

    public function test_factory_archived_state_sets_archived(): void
    {
        $channel = ChatChannel::factory()->archived()->create();

        $this->assertTrue($channel->is_archived);
    }

    public function test_factory_private_state_sets_private_type(): void
    {
        $channel = ChatChannel::factory()->private()->create();

        $this->assertEquals('private', $channel->type);
    }

    public function test_factory_public_state_sets_public_type(): void
    {
        $channel = ChatChannel::factory()->public()->create();

        $this->assertEquals('public', $channel->type);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function test_last_message_attribute_returns_latest_message(): void
    {
        $channel = ChatChannel::factory()->create();
        ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'content' => 'First',
            'created_at' => now()->subHour(),
        ]);
        $latest = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'content' => 'Latest',
            'created_at' => now(),
        ]);

        $lastMessage = $channel->last_message;

        $this->assertNotNull($lastMessage);
        $this->assertEquals('Latest', $lastMessage->content);
    }

    public function test_last_message_attribute_returns_null_for_no_messages(): void
    {
        $channel = ChatChannel::factory()->create();

        $this->assertNull($channel->last_message);
    }

    // =========================================================================
    // HasAgency Trait
    // =========================================================================

    public function test_chat_channel_uses_has_agency_trait(): void
    {
        $agency = Agency::factory()->create();
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id]);

        $this->assertEquals($agency->id, $channel->agency_id);
        $this->assertDatabaseHas('chat_channels', [
            'id' => $channel->id,
            'agency_id' => $agency->id,
        ]);
    }
}
