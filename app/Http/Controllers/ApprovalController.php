<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SocialPost;
use App\Services\Approval\ClientApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $posts = SocialPost::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }
}
