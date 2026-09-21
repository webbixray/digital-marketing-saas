<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPortalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientPortalController extends Controller
{
    /**
     * Show the client portal settings page (agency admin)
     */
    public function settings()
    {
        $settings = ClientPortalSetting::firstOrCreate(
            ['agency_id' => auth()->user()->agency_id],
            [
                'brand_name' => auth()->user()->agency->name,
                'brand_color' => '#6366f1',
                'is_enabled' => true,
                'show_analytics' => true,
                'show_invoices' => true,
                'allow_approvals' => true,
                'show_team_activity' => false,
            ]
        );

        return view('client-portal.settings', compact('settings'));
    }

    /**
     * Update client portal settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'brand_name' => 'required|string|max:255',
            'brand_color' => 'required|string|max:7',
            'logo_url' => 'nullable|url',
            'custom_domain' => 'nullable|string|unique:client_portal_settings',
            'is_enabled' => 'boolean',
            'show_analytics' => 'boolean',
            'show_invoices' => 'boolean',
            'allow_approvals' => 'boolean',
            'show_team_activity' => 'boolean',
            'welcome_message' => 'nullable|string|max:1000',
        ]);

        ClientPortalSetting::updateOrCreate(
            ['agency_id' => auth()->user()->agency_id],
            $validated
        );

        return redirect()->route('client-portal.settings')
            ->with('success', 'Client portal settings updated!');
    }

    /**
     * Generate access token for a client
     */
    public function generateToken(Client $client)
    {
        $this->authorize('update', $client);

        $token = Str::random(64);

        \App\Models\ClientAccessToken::create([
            'client_id' => $client->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addYear(),
        ]);

        return redirect()->back()
            ->with('success', 'Access token generated: ' . $token)
            ->with('token', $token);
    }

    /**
     * Revoke client access token
     */
    public function revokeToken(Client $client, \App\Models\ClientAccessToken $accessToken)
    {
        $this->authorize('update', $client);
        $accessToken->delete();

        return redirect()->back()->with('success', 'Token revoked successfully');
    }

    /**
     * Public client portal view (no auth required, uses token)
     */
    public function show(Request $request, string $token)
    {
        $accessToken = \App\Models\ClientAccessToken::where('token', hash('sha256', $token))
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->first();

        if (!$accessToken) {
            abort(403, 'Invalid or expired access token');
        }

        $accessToken->update(['last_used_at' => now()]);

        $client = $accessToken->client;
        $agency = $client->agency;
        $settings = ClientPortalSetting::where('agency_id', $agency->id)->first();

        // Get client's campaigns
        $campaigns = $client->campaigns()
            ->withCount('socialPosts')
            ->orderByDesc('created_at')
            ->paginate(10);

        // Get client's invoices
        $invoices = $client->invoices()
            ->orderByDesc('created_at')
            ->paginate(10);

        // Calculate stats
        $stats = [
            'total_posts' => $client->socialPosts()->count(),
            'published_posts' => $client->socialPosts()->where('status', 'published')->count(),
            'engagement_rate' => $client->socialPosts()->avg('engagement_rate') ?? 0,
        ];

        return view('client-portal.show', compact(
            'client', 'agency', 'settings', 'campaigns', 'invoices', 'stats', 'token'
        ));
    }

    /**
     * Client approves a post
     */
    public function approvePost(Request $request, string $token, \App\Models\SocialPost $post)
    {
        $accessToken = \App\Models\ClientAccessToken::where('token', hash('sha256', $token))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        if ($post->client_id !== $accessToken->client_id) {
            abort(403);
        }

        $post->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => 'client',
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Client rejects a post with feedback
     */
    public function rejectPost(Request $request, string $token, \App\Models\SocialPost $post)
    {
        $accessToken = \App\Models\ClientAccessToken::where('token', hash('sha256', $token))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        if ($post->client_id !== $accessToken->client_id) {
            abort(403);
        }

        $post->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('reason'),
            'rejected_at' => now(),
            'rejected_by' => 'client',
        ]);

        return response()->json(['success' => true]);
    }
}
