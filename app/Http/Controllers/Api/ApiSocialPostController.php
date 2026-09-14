<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SocialPostRequest;
use App\Http\Resources\SocialPostResource;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSocialPostController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $query = SocialPost::where('agency_id', $agencyId);

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
        $this->authorizeAccess($request, $post);

        return (new SocialPostResource($post->load('campaigns', 'socialAccount')))->response();
    }

    public function update(SocialPostRequest $request, SocialPost $post): JsonResponse
    {
        $this->authorizeAccess($request, $post);
        $post->update($request->validated());

        return (new SocialPostResource($post))->response();
    }

    public function destroy(Request $request, SocialPost $post): JsonResponse
    {
        $this->authorizeAccess($request, $post);
        $post->delete();

        return response()->json(null, 204);
    }

    private function authorizeAccess(Request $request, SocialPost $post): void
    {
        $agencyId = $request->user()->agency_id;
        if ($post->agency_id !== $agencyId) {
            abort(404);
        }
    }
}
