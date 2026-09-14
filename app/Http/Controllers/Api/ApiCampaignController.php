<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiCampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $agencyId = $request->user()->agency_id;
            $query = Campaign::where('agency_id', $agencyId);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $campaigns = $query->orderBy('created_at', 'desc')
                ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

            return CampaignResource::collection($campaigns)->response();
        } catch (\Exception $e) {
            Log::error('Failed to list campaigns', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve campaigns.',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|in:general,social,email,mixed',
                'description' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            $agencyId = $request->user()->agency_id;
            $campaign = Campaign::create([
                'agency_id' => $agencyId,
                'slug' => Str::slug($data['name']).'-'.uniqid(),
                ...$data,
            ]);

            return (new CampaignResource($campaign))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create campaign', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign.',
            ], 500);
        }
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $this->authorizeAccess($request, $campaign);

            return (new CampaignResource($campaign->load('posts')))->response();
        } catch (AuthorizationException|HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to show campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve campaign.',
            ], 500);
        }
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $this->authorizeAccess($request, $campaign);

            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'type' => 'nullable|string|in:general,social,email,mixed',
                'description' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            $campaign->update($data);

            return (new CampaignResource($campaign))->response();
        } catch (AuthorizationException|HttpException $e) {
            throw $e;
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign.',
            ], 500);
        }
    }

    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $this->authorizeAccess($request, $campaign);
            $campaign->delete();

            return response()->json(null, 204);
        } catch (AuthorizationException|HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to delete campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign.',
            ], 500);
        }
    }

    private function authorizeAccess(Request $request, Campaign $campaign): void
    {
        $agencyId = $request->user()->agency_id;
        if ($campaign->agency_id !== $agencyId) {
            abort(404);
        }
    }
}
