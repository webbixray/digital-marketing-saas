<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SocialPostRequest;
use App\Http\Resources\SocialPostResource;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSocialPostController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $query = SocialPost::forAgency($agencyId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        $posts = $query->orderBy('created_at', 'desc')
            ->with('socialAccount')
            ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

        return SocialPostResource::collection($posts)->response();
    }

    public function store(SocialPostRequest $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $validated = $request->validated();
        $validated['platform'] ??= 'twitter';
        $validated['social_account_id'] ??= null;
        $post = SocialPost::create([
            'agency_id' => $agencyId,
            ...$validated,
        ]);

        return (new SocialPostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, SocialPost $post): JsonResponse
    {
        $this->authorizeAgencyResource($post, $request->user()->agency_id);

        return (new SocialPostResource($post->load('socialAccount')))->response();
    }

    public function update(Request $request, SocialPost $post): JsonResponse
    {
        $this->authorizeAgencyResource($post, $request->user()->agency_id);

        $data = $request->validate([
            'content' => 'sometimes|string',
            'status' => 'sometimes|string|in:draft,scheduled,published,failed',
            'scheduled_at' => 'sometimes|date',
        ]);

        $post->update($data);

        return (new SocialPostResource($post))->response();
    }

    public function destroy(Request $request, SocialPost $post): JsonResponse
    {
        $this->authorizeAgencyResource($post, $request->user()->agency_id);
        $post->delete();

        return response()->json(null, 204);
    }
}
