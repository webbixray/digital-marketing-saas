<?php

namespace App\Http\Controllers;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show chat index page
     */
    public function index(): View
    {
        $channels = ChatChannel::forCurrentAgency()
            ->active()
            ->with(['users', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('users')
            ->orderByDesc('updated_at')
            ->get();

        $activeChannel = $channels->first();

        return view('chat.index', compact('channels', 'activeChannel'));
    }

    /**
     * Show a specific channel
     */
    public function show(ChatChannel $channel, Request $request): View
    {
        // Verify channel belongs to user's agency
        $user = auth()->user();
        if ($user === null || $channel->agency_id !== $user->agency_id) {
            abort(403);
        }

        $channels = ChatChannel::forCurrentAgency()
            ->active()
            ->with(['users', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('users')
            ->orderByDesc('updated_at')
            ->get();

        $messages = ChatMessage::where('channel_id', $channel->id)
            ->with(['user', 'replyTo', 'reactions'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return view('chat.show', compact('channels', 'channel', 'messages'));
    }
}
