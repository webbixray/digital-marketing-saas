<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Social\PinterestApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PinterestController extends Controller
{
    public function __construct(
        private readonly PinterestApiService $pinterest,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show Pinterest integration dashboard.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $pinterestAccounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'pinterest')
            ->active()
            ->get();

        return view('pinterest.index', compact('pinterestAccounts'));
    }

    /**
     * Connect a Pinterest account via OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $state = Str::random(32);
        $request->session()->put('pinterest_state', $state);
        $request->session()->put('pinterest_connect_agency_id', $agencyId);

        $authUrl = $this->pinterest->getAuthUrl(
            route('pinterest.callback'),
            $state
        );

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from Pinterest.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->get('state') !== $request->session()->get('pinterest_state')) {
            return redirect()->route('pinterest.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            Log::error('Pinterest OAuth error', [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
            ]);
            return redirect()->route('pinterest.index')
                ->with('error', 'Pinterest authorization failed: ' . $request->get('error_description'));
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('pinterest.index')
                ->with('error', 'No authorization code received.');
        }

        $tokenResult = $this->pinterest->exchangeCodeForToken($code, route('pinterest.callback'));

        if (!$tokenResult['success']) {
            Log::error('Pinterest token exchange failed', $tokenResult);
            return redirect()->route('pinterest.index')
                ->with('error', 'Failed to connect Pinterest: ' . ($tokenResult['error'] ?? 'Unknown error'));
        }

        $accessToken = $tokenResult['access_token'];
        $expiresIn = $tokenResult['expires_in'] ?? null;

        $profileResult = $this->pinterest->getUserProfile($accessToken);

        if (!$profileResult['success']) {
            return redirect()->route('pinterest.index')
                ->with('error', 'Failed to fetch Pinterest profile.');
        }

        $profile = $profileResult['data'];
        $agencyId = $request->session()->get('pinterest_connect_agency_id');

        // Check if account is already connected
        $existingAccount = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'pinterest')
            ->where('platform_account_id', $profile['username'] ?? '')
            ->first();

        $accountData = [
            'platform' => 'pinterest',
            'platform_account_id' => $profile['username'] ?? null,
            'platform_username' => $profile['username'] ?? null,
            'platform_display_name' => $profile['username'] ?? null,
            'platform_account_type' => 'business',
            'access_token' => $accessToken,
            'refresh_token' => $tokenResult['refresh_token'] ?? null,
            'token_type' => 'bearer',
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'metadata' => [
                'account_type' => $profile['account_type'] ?? null,
            ],
            'is_active' => true,
            'is_verified' => false,
        ];

        if ($existingAccount) {
            $existingAccount->update($accountData);
        } else {
            SocialAccount::create(array_merge($accountData, [
                'agency_id' => $agencyId,
            ]));
        }

        $request->session()->forget(['pinterest_state', 'pinterest_connect_agency_id']);

        return redirect()->route('pinterest.index')
            ->with('success', 'Pinterest account connected successfully!');
    }

    /**
     * Disconnect a Pinterest account.
     */
    public function disconnect(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('platform', 'pinterest')
            ->find($accountId);

        if (!$account) {
            abort(404, 'Pinterest account not found.');
        }

        if ((int) $account->agency_id !== (int) $agencyId) {
            abort(403, 'You do not have permission to disconnect this account.');
        }

        $account->delete();

        return redirect()->route('pinterest.index')
            ->with('success', 'Pinterest account disconnected.');
    }

    /**
     * Toggle account active status.
     */
    public function toggle(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'pinterest')
            ->findOrFail($accountId);

        $account->update(['is_active' => !$account->is_active]);

        return redirect()->route('pinterest.index')
            ->with('success', 'Pinterest account status updated.');
    }

    /**
     * Get Pinterest account metrics.
     */
    public function metrics(Request $request, int $accountId): View|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'pinterest')
            ->findOrFail($accountId);

        if (!$account->isExpired()) {
            $profile = $this->pinterest->getUserProfile($account->access_token);
            $boards = $this->pinterest->getBoards($account->access_token);
        } else {
            $profile = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
            $boards = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
        }

        return view('pinterest.metrics', compact('account', 'profile', 'boards'));
    }

    /**
     * Refresh Pinterest token.
     */
    public function refreshToken(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'pinterest')
            ->findOrFail($accountId);

        if (!$account->refresh_token) {
            return redirect()->route('pinterest.index')
                ->with('error', 'No refresh token available. Please reconnect.');
        }

        $result = $this->pinterest->refreshToken($account->refresh_token);

        if (!$result['success']) {
            return redirect()->route('pinterest.index')
                ->with('error', 'Token refresh failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $account->update([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_expires_at' => now()->addSeconds($result['expires_in']),
        ]);

        return redirect()->route('pinterest.index')
            ->with('success', 'Token refreshed successfully!');
    }

    /**
     * API: Create a Pinterest pin.
     */
    public function apiCreatePin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
            'board_id' => 'required|string',
            'title' => 'required|string|max=100',
            'description' => 'required|string|max=500',
            'image_url' => 'required|url',
            'link' => 'nullable|url',
        ]);

        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('id', $validated['account_id'])
            ->where('platform', 'pinterest')
            ->first();

        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        if ($account->isExpired()) {
            return response()->json(['error' => 'Token expired. Please reconnect.'], 401);
        }

        $result = $this->pinterest->createPin(
            $account->access_token,
            $validated['board_id'],
            $validated['title'],
            $validated['description'],
            $validated['image_url'],
            $validated['link'] ?? null
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'pin_id' => $result['pin_id'],
        ]);
    }
}
