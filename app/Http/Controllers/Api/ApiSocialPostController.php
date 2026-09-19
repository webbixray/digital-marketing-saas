<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SocialPostRequest;
use App\Http\Resources\SocialPostResource;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiSocialPostController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('API social post index failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch social posts'], 500);
        }
    }

    public function store(SocialPostRequest $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('API social post store failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create social post'], 500);
        }
    }

    public function show(Request $request, SocialPost $post): JsonResponse
    {
        try {
            $this->authorizeAgencyResource($post, $request->user()->agency_id);
            return (new SocialPostResource($post->load('socialAccount')))->response();
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json(['error' => 'Not found'], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            Log::error('API social post show failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch social post'], 500);
        }
    }

    public function update(Request $request, SocialPost $post): JsonResponse
    {
        try {
            $this->authorizeAgencyResource($post, $request->user()->agency_id);

            $data = $request->validate([
                'content' => 'sometimes|string',
                'status' => 'sometimes|string|in:draft,scheduled,published,failed',
                'scheduled_at' => 'sometimes|date',
            ]);

            $post->update($data);

            return (new SocialPostResource($post))->response();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            Log::error('API social post update failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update social post'], 500);
        }
    }

    public function destroy(Request $request, SocialPost $post): JsonResponse
    {
        try {
            $this->authorizeAgencyResource($post, $request->user()->agency_id);
            $post->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            Log::error('API social post destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete social post'], 500);
        }
    }
}
