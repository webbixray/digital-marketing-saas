<?php

namespace App\Http\Controllers;

use App\Services\Social\UnifiedInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnifiedInboxController extends Controller
{
    public function __construct(
        private readonly UnifiedInboxService $inboxService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show unified inbox dashboard.
     */
    public function index(Request $request): View
    {
        $agency = $request->user()->agency;
        $inbox = $this->inboxService->getInbox($agency, $request->only([
            'platform', 'type', 'status', 'search', 'date_from', 'date_to', 'per_page',
        ]));

        return view('inbox.index', compact('inbox'));
    }

    /**
     * Show a single message.
     */
    public function show(Request $request, int $messageId): View
    {
        $agency = $request->user()->agency;
        $message = $this->inboxService->getMessage($agency, $messageId);

        if (!$message) {
            abort(404);
        }

        // Auto-mark as read
        if ($message->status === \App\Enums\InboxMessageStatus::UNREAD->value) {
            $this->inboxService->markAsRead($agency, $messageId);
        }

        return view('inbox.show', compact('message'));
    }

    /**
     * API: Get inbox data.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $inbox = $this->inboxService->getInbox($agency, $request->only([
            'platform', 'type', 'status', 'search', 'date_from', 'date_to', 'per_page',
        ]));

        return response()->json(['success' => true, 'data' => $inbox]);
    }

    /**
     * API: Mark message as read.
     */
    public function apiMarkRead(Request $request, int $messageId): JsonResponse
    {
        $agency = $request->user()->agency;
        $success = $this->inboxService->markAsRead($agency, $messageId);

        return response()->json(['success' => $success]);
    }

    /**
     * API: Mark all messages as read.
     */
    public function apiMarkAllRead(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $count = $this->inboxService->markAllAsRead($agency);

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * API: Reply to a message.
     */
    public function apiReply(Request $request, int $messageId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max=5000',
        ]);

        $agency = $request->user()->agency;
        $success = $this->inboxService->replyToMessage(
            $agency,
            $messageId,
            $validated['content'],
            $request->user()
        );

        return response()->json(['success' => $success]);
    }

    /**
     * API: Delete a message.
     */
    public function apiDelete(Request $request, int $messageId): JsonResponse
    {
        $agency = $request->user()->agency;
        $success = $this->inboxService->deleteMessage($agency, $messageId);

        return response()->json(['success' => $success]);
    }
}
