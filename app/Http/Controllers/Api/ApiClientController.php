<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $query = Client::where('agency_id', $agencyId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $clients = $query->orderBy('created_at', 'desc')
            ->with('subscriptions')
            ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

        return ClientResource::collection($clients)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $agencyId = $request->user()->agency_id;
        $client = Client::create([
            'agency_id' => $agencyId,
            ...$data,
        ]);

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorizeAccess($request, $client);

        return (new ClientResource($client->load('campaigns')))->response();
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $this->authorizeAccess($request, $client);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($data);

        return (new ClientResource($client))->response();
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorizeAccess($request, $client);
        $client->delete();

        return response()->json(null, 204);
    }

    private function authorizeAccess(Request $request, Client $client): void
    {
        $agencyId = $request->user()->agency_id;
        if ($client->agency_id !== $agencyId) {
            abort(404);
        }
    }
}
