<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiChatController extends Controller
{
    /**
     * List channels for the agency
     */
    public function channels(): JsonResponse
    {
        $channels = ChatChannel::forCurrentAgency()
            ->active()
            ->with(['users', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->withCount('users')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'type' => $c->type,
                'is_archived' => $c->is_archived,
                'users_count' => $c->users_count,
                'last_message' => $c->last_message ? [
                    'content' => Str::limit($c->last_message->content, 50),
                    'user' => $c->last_message->user->name,
                    'created_at' => $c->last_message->created_at->toISOString(),
                ] : null,
                'unread_count' => $c->unread_count,
                'created_at' => $c->created_at->toISOString(),
            ]);

        return response()->json(['channels' => $channels]);
    }

    /**
     * Create a new channel
     */
    public function createChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:public,private',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $channel = ChatChannel::create([
            'agency_id' => auth()->user()->agency_id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'created_by' => auth()->id(),
        ]);

        // Add users to channel
        $channel->users()->attach(array_merge(
            $validated['user_ids'],
            [auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'channel' => $channel,
        ], 201);
    }

    /**
     * Get messages in a channel
     */
    public function messages(ChatChannel $channel): JsonResponse
    {
        $messages = ChatMessage::where('channel_id', $channel->id)
            ->with(['user', 'replyTo', 'reactions'])
            ->recent()
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, ChatChannel $channel): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'reply_to_id' => 'nullable|exists:chat_messages,id',
        ]);

        $message = ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'reply_to_id' => $validated['reply_to_id'] ?? null,
        ]);

        $message->load('user', 'replyTo', 'reactions');

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }

    /**
     * Add reaction to a message
     */
    public function addReaction(Request $request, ChatMessage $message): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $reaction = ChatReaction::firstOrCreate([
            'message_id' => $message->id,
            'user_id' => auth()->id(),
            'emoji' => $validated['emoji'],
        ]);

        return response()->json([
            'success' => true,
            'reaction' => $reaction,
        ]);
    }

    /**
     * Remove reaction from a message
     */
    public function removeReaction(ChatMessage $message, string $emoji): JsonResponse
    {
        ChatReaction::where('message_id', $message->id)
            ->where('user_id', auth()->id())
            ->where('emoji', $emoji)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Mark channel as read
     */
    public function markAsRead(ChatChannel $channel): JsonResponse
    {
        $channel->users()->updateExistingPivot(auth()->id(), [
            'last_read_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a message
     */
    public function deleteMessage(ChatMessage $message): JsonResponse
    {
        if ($message->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->update(['is_deleted' => true, 'content' => 'This message was deleted']);

        return response()->json(['success' => true]);
    }

    /**
     * Edit a message
     */
    public function editMessage(Request $request, ChatMessage $message): JsonResponse
    {
        if ($message->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message->update([
            'content' => $validated['content'],
            'is_edited' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
