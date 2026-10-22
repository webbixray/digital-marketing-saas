<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show Telegram linking page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $linked = ! is_null($user->telegram_chat_id);

        // Generate a unique linking code if not linked
        if (! $linked && ! $user->telegram_link_code) {
            $user->update(['telegram_link_code' => Str::random(32)]);
        }

        return view('telegram.index', compact('user', 'linked'));
    }

    /**
     * Link account via code from Telegram bot.
     */
    public function link(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_chat_id' => 'required|string',
        ]);

        $user = $request->user();

        // Check if another user already has this chat ID
        $existing = User::where('telegram_chat_id', $validated['telegram_chat_id'])
            ->where('id', '!=', $user->id)
            ->first();

        if ($existing) {
            return back()->with('error', 'This Telegram account is already linked to another user.');
        }

        $user->update([
            'telegram_chat_id' => $validated['telegram_chat_id'],
            'telegram_link_code' => null,
            'telegram_linked_at' => now(),
        ]);

        return back()->with('success', 'Telegram account linked successfully! You can now use the bot.');
    }

    /**
     * Unlink Telegram account.
     */
    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'telegram_chat_id' => null,
            'telegram_link_code' => null,
            'telegram_linked_at' => null,
        ]);

        return back()->with('success', 'Telegram account unlinked.');
    }

    /**
     * Generate a new link code.
     */
    public function regenerateCode(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['telegram_link_code' => Str::random(32)]);

        return back()->with('success', 'New link code generated.');
    }

    /**
     * API: Link via Telegram bot command.
     */
    public function linkViaBot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:32',
            'telegram_chat_id' => 'required|string',
            'telegram_username' => 'nullable|string',
        ]);

        $user = User::where('telegram_link_code', $validated['code'])->first();

        if (! $user) {
            return response()->json(['error' => 'Invalid link code'], 404);
        }

        $user->update([
            'telegram_chat_id' => $validated['telegram_chat_id'],
            'telegram_username' => $validated['telegram_username'] ?? null,
            'telegram_link_code' => null,
            'telegram_linked_at' => now(),
        ]);

        return response()->json(['message' => 'Account linked successfully']);
    }
}
