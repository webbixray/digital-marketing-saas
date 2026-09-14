<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiCampaignController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $query = Campaign::where('agency_id', $agencyId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->orderBy('created_at', 'desc')
            ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

        return CampaignResource::collection($campaigns)->response();
    }

    public function store(Request $request): JsonResponse
    {
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
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeAgencyResource($campaign, $request->user()->agency_id);

        return (new CampaignResource($campaign->load('posts')))->response();
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeAgencyResource($campaign, $request->user()->agency_id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'nullable|string|in:general,social,email,mixed',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $campaign->update($data);

        return (new CampaignResource($campaign))->response();
    }

    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeAgencyResource($campaign, $request->user()->agency_id);
        $campaign->delete();

        return response()->json(null, 204);
    }
}
