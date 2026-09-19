<?php

namespace App\Http\Controllers\Api;

use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiSocialAccountController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $agencyId = $request->user()->agency_id;
            $accounts = SocialAccount::where('agency_id', $agencyId)
                ->orderBy('created_at', 'desc')
                ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

            return response()->json($accounts);
        } catch (\Exception $e) {
            Log::error('API social account index failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch social accounts'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('API social account store failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create social account'], 500);
        }
    }

    public function show(Request $request, SocialAccount $account): JsonResponse
    {
        try {
            $this->authorizeAgencyResource($account, $request->user()->agency_id);
            return response()->json($account);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            Log::error('API social account show failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch social account'], 500);
        }
    }

    public function update(Request $request, SocialAccount $account): JsonResponse
    {
        try {
            $this->authorizeAgencyResource($account, $request->user()->agency_id);

            $data = $request->validate([
                'platform_display_name' => 'nullable|string|max:255',
                'platform_username' => 'nullable|string|max:255',
            ]);

            $account->update($data);

            return response()->json($account);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            Log::error('API social account update failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update social account'], 500);
        }
    }

    public function destroy(Request $request, SocialAccount $account): JsonResponse
    {
        try {
            $this->authorizeAgencyResource($account, $request->user()->agency_id);
            $account->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            Log::error('API social account destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete social account'], 500);
        }
    }
}
