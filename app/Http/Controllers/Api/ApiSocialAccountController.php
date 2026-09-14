<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSocialAccountController extends Controller
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
        $this->authorizeAccess($request, $account);

        return response()->json($account);
    }

    public function update(Request $request, SocialAccount $account): JsonResponse
    {
        $this->authorizeAccess($request, $account);

        $data = $request->validate([
            'account_name' => 'sometimes|string|max:255',
            'account_handle' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $account->update($data);

        return response()->json($account);
    }

    public function destroy(Request $request, SocialAccount $account): JsonResponse
    {
        $this->authorizeAccess($request, $account);
        $account->delete();

        return response()->json(null, 204);
    }

    private function authorizeAccess(Request $request, SocialAccount $account): void
    {
        $agencyId = $request->user()->agency_id;
        if ($account->agency_id !== $agencyId) {
            abort(404);
        }
    }
}
