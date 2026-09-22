<?php

namespace App\Http\Controllers;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Events\Chat\ChatMessageSent;
use App\Events\Chat\ChatTyping;
use App\Events\Chat\ChatRead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class Chat2Controller extends Controller
{
    /**
     * Show chat index page with sidebar
     */
    public function index()
    {
        $channels = ChatChannel::forCurrentAgency()
            ->active()
            ->with(['users', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->withCount('users')
            ->orderByDesc('updated_at')
            ->get();

        $activeChannel = $channels->first();

        $messages = collect();
        if ($activeChannel) {
            $messages = ChatMessage::where('channel_id', $activeChannel->id)
                ->with(['user', 'replyTo', 'reactions'])
                ->orderBy('created_at', 'asc')
                ->paginate(50);
        }

        return view('chat.index', compact('channels', 'activeChannel', 'messages'));
    }

    /**
     * Show full-screen channel chat
     */
    public function channel(ChatChannel $channel)
    {
        // Verify channel belongs to user's agency
        if ($channel->agency_id !== auth()->user()->agency_id) {
            abort(403);
        }

        $channels = ChatChannel::forCurrentAgency()
            ->active()
            ->with(['users', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->withCount('users')
            ->orderByDesc('updated_at')
            ->get();

        $messages = ChatMessage::where('channel_id', $channel->id)
            ->with(['user', 'replyTo', 'reactions'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Mark channel as read
        $channel->users()->updateExistingPivot(auth()->id(), [
            'last_read_at' => now(),
        ]);

        return view('chat.channel', compact('channels', 'channel', 'messages'));
    }

    /**
     * Send a message (AJAX endpoint)
     */
    public function sendMessage(Request $request, ChatChannel $channel): JsonResponse
    {
        // Verify channel belongs to user's agency
        if ($channel->agency_id !== auth()->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'reply_to_id' => 'nullable|exists:chat_messages,id',
            'file_url' => 'nullable|string|max:1000',
            'file_name' => 'nullable|string|max:255',
            'file_type' => 'nullable|string|max:100',
            'file_size' => 'nullable|integer|max:10485760',
        ]);

        $message = ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'reply_to_id' => $validated['reply_to_id'] ?? null,
            'file_url' => $validated['file_url'] ?? null,
            'file_name' => $validated['file_name'] ?? null,
            'file_type' => $validated['file_type'] ?? null,
            'file_size' => $validated['file_size'] ?? null,
            'type' => isset($validated['file_url']) ? 'file' : 'text',
        ]);

        $message->load('user', 'replyTo', 'reactions');

        // Broadcast event
        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }

    /**
     * Handle typing indicator
     */
    public function typing(Request $request, ChatChannel $channel): JsonResponse
    {
        if ($channel->agency_id !== auth()->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $isTyping = $request->boolean('typing', true);

        broadcast(new ChatTyping($channel->id, auth()->user(), $isTyping))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Mark channel as read
     */
    public function read(ChatChannel $channel): JsonResponse
    {
        if ($channel->agency_id !== auth()->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $channel->users()->updateExistingPivot(auth()->id(), [
            'last_read_at' => now(),
        ]);

        broadcast(new ChatRead($channel->id, auth()->user()))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Add reaction to message
     */
    public function addReaction(Request $request, ChatMessage $message): JsonResponse
    {
        if ($message->channel->agency_id !== auth()->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
     * Remove reaction from message
     */
    public function removeReaction(ChatMessage $message, string $emoji): JsonResponse
    {
        if ($message->channel->agency_id !== auth()->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        ChatReaction::where('message_id', $message->id)
            ->where('user_id', auth()->id())
            ->where('emoji', $emoji)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Edit a message
     */
    public function editMessage(Request $request, ChatMessage $message): JsonResponse
    {
        if ($message->channel->agency_id !== auth()->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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

    /**
     * Delete a message
     */
    public function deleteMessage(ChatMessage $message): JsonResponse
    {
        if ($message->channel->agency_id !== auth()->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($message->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->update(['is_deleted' => true, 'content' => 'This message was deleted']);

        return response()->json(['success' => true]);
    }
}
