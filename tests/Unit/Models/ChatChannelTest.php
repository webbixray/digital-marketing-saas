<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatChannelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Scopes
    // =========================================================================

    #[Test]
    public function scope_for_current_agency_filters_by_authenticated_user_agency(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);

        ChatChannel::factory()->create(['agency_id' => $agency->id]);
        ChatChannel::factory()->create(['agency_id' => Agency::factory()->create()->id]);

        $this->actingAs($user);

        $channels = ChatChannel::forCurrentAgency()->get();

        $this->assertCount(1, $channels);
        $this->assertEquals($agency->id, $channels->first()->agency_id);
    }

    #[Test]
    public function scope_active_excludes_archived_channels(): void
    {
        ChatChannel::factory()->create(['is_archived' => false]);
        ChatChannel::factory()->create(['is_archived' => true]);

        $channels = ChatChannel::active()->get();

        $this->assertCount(1, $channels);
        $this->assertFalse($channels->first()->is_archived);
    }

    #[Test]
    public function scope_public_filters_by_type_public(): void
    {
        ChatChannel::factory()->public()->create();
        ChatChannel::factory()->private()->create();

        $channels = ChatChannel::public()->get();

        $this->assertCount(1, $channels);
        $this->assertEquals('public', $channels->first()->type);
    }

    #[Test]
    public function scope_for_user_returns_only_channels_user_belongs_to(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $otherUser = User::factory()->withAgency()->create(['agency_id' => $agency->id]);

        $channelForUser = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);
        $channelForUser->users()->attach($user);

        $channelForOther = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);
        $channelForOther->users()->attach($otherUser);

        $channels = ChatChannel::forUser($user)->get();

        $this->assertCount(1, $channels);
        $this->assertEquals($channelForUser->id, $channels->first()->id);
    }

    #[Test]
    public function scopes_can_be_chained(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);

        ChatChannel::factory()->public()->create(['agency_id' => $agency->id, 'is_archived' => false, 'created_by' => $user->id]);
        ChatChannel::factory()->private()->create(['agency_id' => $agency->id, 'is_archived' => false, 'created_by' => $user->id]);
        ChatChannel::factory()->archived()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);

        $channels = ChatChannel::public()->active()->get();

        $this->assertCount(1, $channels);
        $this->assertEquals('public', $channels->first()->type);
        $this->assertFalse($channels->first()->is_archived);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    #[Test]
    public function channel_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $channel->agency);
        $this->assertEquals($agency->id, $channel->agency->id);
    }

    #[Test]
    public function channel_has_many_users_via_pivot(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);
        $channel->users()->attach($user);

        $this->assertCount(1, $channel->users);
        $this->assertInstanceOf(User::class, $channel->users->first());
        $this->assertEquals($user->id, $channel->users->first()->id);
    }

    #[Test]
    public function channel_users_pivot_has_additional_columns(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);
        $channel->users()->attach($user, [
            'last_read_at' => now()->subHour(),
            'is_moderator' => true,
        ]);

        $pivot = $channel->users->first()->pivot;

        $this->assertNotNull($pivot->last_read_at);
        $this->assertEquals(1, $pivot->is_moderator);
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    #[Test]
    public function channel_has_many_messages(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);
        ChatMessage::factory()->count(3)->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $channel->messages);
        $this->assertInstanceOf(ChatMessage::class, $channel->messages->first());
    }

    #[Test]
    public function channel_belongs_to_creator(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $channel->creator);
        $this->assertEquals($user->id, $channel->creator->id);
    }

    // =========================================================================
    // Accessors / Attributes
    // =========================================================================

    #[Test]
    public function last_message_accessor_returns_most_recent_message(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);

        ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'content' => 'first',
            'created_at' => now()->subMinutes(10),
        ]);

        $latest = ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'content' => 'latest',
            'created_at' => now(),
        ]);

        $this->assertEquals($latest->id, $channel->last_message->id);
        $this->assertEquals('latest', $channel->last_message->content);
    }

    #[Test]
    public function last_message_accessor_returns_null_when_no_messages(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);

        $this->assertNull($channel->last_message);
    }

    #[Test]
    public function unread_count_accessor_returns_all_when_user_has_no_pivot(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);
        ChatMessage::factory()->count(5)->create(['channel_id' => $channel->id, 'user_id' => $user->id]);

        $this->actingAs($user);

        $this->assertEquals(5, $channel->unread_count);
    }

    #[Test]
    public function unread_count_accessor_returns_count_of_unread_messages(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);

        // Read messages
        ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(2),
        ]);

        // Unread messages
        ChatMessage::factory()->count(3)->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'created_at' => now()->subMinute(),
        ]);

        $channel->users()->attach($user, ['last_read_at' => now()->subHour()]);

        $this->actingAs($user);

        $this->assertEquals(3, $channel->unread_count);
    }

    #[Test]
    public function unread_count_accessor_returns_zero_when_all_messages_read(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);
        $channel = ChatChannel::factory()->create(['agency_id' => $agency->id, 'created_by' => $user->id]);

        ChatMessage::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'created_at' => now()->subHour(),
        ]);

        $channel->users()->attach($user, ['last_read_at' => now()]);

        $this->actingAs($user);

        $this->assertEquals(0, $channel->unread_count);
    }

    // =========================================================================
    // Model Attributes / Configuration
    // =========================================================================

    #[Test]
    public function fillable_attributes_are_correct(): void
    {
        $channel = new ChatChannel();

        $this->assertEquals([
            'agency_id',
            'name',
            'slug',
            'description',
            'type',
            'created_by',
            'is_archived',
        ], $channel->getFillable());
    }

    #[Test]
    public function casts_are_correct(): void
    {
        $channel = new ChatChannel();

        $this->assertArrayHasKey('is_archived', $channel->getCasts());
        $this->assertEquals('boolean', $channel->getCasts()['is_archived']);
    }

    #[Test]
    public function is_archived_casts_to_boolean(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);

        $channel = ChatChannel::factory()->create([
            'agency_id' => $agency->id,
            'created_by' => $user->id,
            'is_archived' => 1,
        ]);

        $this->assertIsBool($channel->is_archived);
        $this->assertTrue($channel->is_archived);
    }

    #[Test]
    public function channel_can_be_created(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->withAgency()->create(['agency_id' => $agency->id]);

        $channel = ChatChannel::factory()->create([
            'agency_id' => $agency->id,
            'created_by' => $user->id,
            'name' => 'General',
            'slug' => 'general-' . uniqid(),
            'type' => 'public',
        ]);

        $this->assertDatabaseHas('chat_channels', [
            'id' => $channel->id,
            'name' => 'General',
            'type' => 'public',
        ]);
    }
}
