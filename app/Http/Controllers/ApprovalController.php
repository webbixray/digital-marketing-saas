<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SocialPost;
use App\Services\Approval\ClientApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ClientApprovalService $approvalService
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function submit(Request $request, SocialPost $post): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $this->approvalService->submitForApproval($post, $validated['client_id']);

        return response()->json([
            'success' => true,
            'message' => 'Post submitted for approval.',
        ]);
    }

    public function approve(Request $request, SocialPost $post): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->approvalService->approve($post, auth()->id(), $validated['notes'] ?? null);

        Log::info('Post approved', [
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'agency_id' => $request->user()->agency_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post approved.',
        ]);
    }

    public function reject(Request $request, SocialPost $post): JsonResponse
    {
        $validated = $request->validate([
            'feedback' => 'required|string|max:1000',
        ]);

        $this->approvalService->reject($post, auth()->id(), $validated['feedback']);

        Log::info('Post rejected', [
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'agency_id' => $request->user()->agency_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post rejected with feedback.',
        ]);
    }

    public function pending(): JsonResponse
    {
        $posts = SocialPost::where('agency_id', auth()->user()->agency_id)
            ->where('approval_status', 'pending')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    public function clientPosts(Client $client): JsonResponse
    {
        if ((int) $client->agency_id !== (int) auth()->user()->agency_id) {
            abort(403);
        }

        $posts = SocialPost::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }
}
