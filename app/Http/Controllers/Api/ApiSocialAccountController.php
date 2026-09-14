<?php

namespace App\Http\Controllers\Api;

use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSocialAccountController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $accounts = SocialAccount::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

        return response()->json($accounts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok,pinterest',
            'platform_display_name' => 'nullable|string|max:255',
            'platform_username' => 'nullable|string|max:255',
            'access_token' => 'required|string',
        ]);

        $agencyId = $request->user()->agency_id;
        $account = SocialAccount::create([
            'agency_id' => $agencyId,
            ...$data,
        ]);

        return response()->json($account, 201);
    }

    public function show(Request $request, SocialAccount $account): JsonResponse
    {
        $this->authorizeAgencyResource($account, $request->user()->agency_id);

        return response()->json($account);
    }

    public function update(Request $request, SocialAccount $account): JsonResponse
    {
        $this->authorizeAgencyResource($account, $request->user()->agency_id);

        $data = $request->validate([
            'platform_display_name' => 'nullable|string|max:255',
            'platform_username' => 'nullable|string|max:255',
        ]);

        $account->update($data);

        return response()->json($account);
    }

    public function destroy(Request $request, SocialAccount $account): JsonResponse
    {
        $this->authorizeAgencyResource($account, $request->user()->agency_id);
        $account->delete();

        return response()->json(null, 204);
    }
}
