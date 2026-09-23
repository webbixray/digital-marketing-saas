<?php

namespace Tests\Unit\Models;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMessageModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Basic Creation & Fillable
    // =========================================================================

    public function test_chat_message_can_be_created_with_factory(): void
    {
        $message = ChatMessage::factory()->create();

        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'content' => $message->content,
        ]);
    }

    public function test_chat_message_fillable_attributes(): void
    {
        $channel = ChatChannel::factory()->create();
        $user = User::factory()->create();

        $message = ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'content' => 'Hello everyone!',
            'type' => 'text',
            'file_url' => null,
            'file_name' => null,
            'file_type' => null,
            'file_size' => null,
            'reply_to_id' => null,
            'is_edited' => false,
            'is_deleted' => false,
        ]);

        $this->assertNotNull($message);
        $this->assertEquals('Hello everyone!', $message->content);
        $this->assertEquals('text', $message->type);
        $this->assertFalse($message->is_edited);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function test_chat_message_belongs_to_channel(): void
    {
        $channel = ChatChannel::factory()->create();
        $message = ChatMessage::factory()->create(['channel_id' => $channel->id]);

        $this->assertInstanceOf(ChatChannel::class, $message->channel);
        $this->assertEquals($channel->id, $message->channel->id);
    }

    public function test_chat_message_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $message = ChatMessage::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $message->user);
        $this->assertEquals($user->id, $message->user->id);
    }

    public function test_chat_message_has_many_reactions(): void
    {
        $message = ChatMessage::factory()->create();
        ChatReaction::factory()->count(3)->create(['message_id' => $message->id]);

        $this->assertCount(3, $message->reactions);
        $this->assertInstanceOf(ChatReaction::class, $message->reactions->first());
    }

    public function test_chat_message_belongs_to_reply_to(): void
    {
        $original = ChatMessage::factory()->create();
        $reply = ChatMessage::factory()->create(['reply_to_id' => $original->id]);

        $this->assertInstanceOf(ChatMessage::class, $reply->replyTo);
        $this->assertEquals($original->id, $reply->replyTo->id);
    }

    public function test_chat_message_reply_to_returns_null_when_no_reply(): void
    {
        $message = ChatMessage::factory()->create(['reply_to_id' => null]);

        $this->assertNull($message->replyTo);
    }

    // =========================================================================
    // Casts
    // =========================================================================

    public function test_is_edited_casts_to_boolean(): void
    {
        $message = ChatMessage::factory()->create(['is_edited' => 1]);

        $this->assertIsBool($message->is_edited);
        $this->assertTrue($message->is_edited);
    }

    public function test_is_deleted_casts_to_boolean(): void
    {
        $message = ChatMessage::factory()->create(['is_deleted' => 1]);

        $this->assertIsBool($message->is_deleted);
        $this->assertTrue($message->is_deleted);
    }

    public function test_file_size_casts_to_integer(): void
    {
        $message = ChatMessage::factory()->create(['file_size' => '1024']);

        $this->assertIsInt($message->file_size);
        $this->assertEquals(1024, $message->file_size);
    }

    public function test_is_edited_false_casts_to_boolean(): void
    {
        $message = ChatMessage::factory()->create(['is_edited' => 0]);

        $this->assertIsBool($message->is_edited);
        $this->assertFalse($message->is_edited);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function test_scope_recent_orders_by_desc_created_at(): void
    {
        ChatMessage::factory()->create(['created_at' => now()->subHours(3)]);
        ChatMessage::factory()->create(['created_at' => now()->subHours(1)]);
        ChatMessage::factory()->create(['created_at' => now()->subHours(2)]);

        $messages = ChatMessage::recent()->get();

        $this->assertEquals(now()->subHours(1)->timestamp, $messages->first()->created_at->timestamp);
    }

    public function test_scope_in_channel_filters_by_channel(): void
    {
        $channel1 = ChatChannel::factory()->create();
        $channel2 = ChatChannel::factory()->create();
        ChatMessage::factory()->create(['channel_id' => $channel1->id]);
        ChatMessage::factory()->create(['channel_id' => $channel2->id]);
        ChatMessage::factory()->create(['channel_id' => $channel1->id]);

        $messages = ChatMessage::inChannel($channel1->id)->get();

        $this->assertCount(2, $messages);
        $messages->each(function ($msg) use ($channel1) {
            $this->assertEquals($channel1->id, $msg->channel_id);
        });
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function test_reply_to_accessor_returns_parent_message(): void
    {
        $original = ChatMessage::factory()->create();
        $reply = ChatMessage::factory()->create(['reply_to_id' => $original->id]);

        $this->assertInstanceOf(ChatMessage::class, $reply->reply_to);
        $this->assertEquals($original->id, $reply->reply_to->id);
    }

    public function test_reactions_summary_returns_grouped_emoji_counts(): void
    {
        $message = ChatMessage::factory()->create();
        ChatReaction::factory()->count(2)->create([
            'message_id' => $message->id,
            'emoji' => '👍',
        ]);
        ChatReaction::factory()->create([
            'message_id' => $message->id,
            'emoji' => '❤️',
        ]);

        $summary = $message->reactions_summary;

        $this->assertArrayHasKey('👍', $summary);
        $this->assertArrayHasKey('❤️', $summary);
        $this->assertEquals(2, $summary['👍']);
        $this->assertEquals(1, $summary['❤️']);
    }

    public function test_reactions_summary_returns_empty_array_for_no_reactions(): void
    {
        $message = ChatMessage::factory()->create();

        $summary = $message->reactions_summary;

        $this->assertIsArray($summary);
        $this->assertEmpty($summary);
    }

    // =========================================================================
    // Factory States
    // =========================================================================

    public function test_factory_edited_state_sets_is_edited(): void
    {
        $message = ChatMessage::factory()->edited()->create();

        $this->assertTrue($message->is_edited);
    }

    public function test_factory_deleted_state_sets_is_deleted(): void
    {
        $message = ChatMessage::factory()->deleted()->create();

        $this->assertTrue($message->is_deleted);
    }

    public function test_factory_with_file_state_sets_file_attributes(): void
    {
        $message = ChatMessage::factory()->withFile()->create();

        $this->assertEquals('https://example.com/file.pdf', $message->file_url);
        $this->assertEquals('file.pdf', $message->file_name);
        $this->assertEquals('application/pdf', $message->file_type);
        $this->assertEquals(1024, $message->file_size);
    }
}
