<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPortalSetting;
use App\Models\SocialPost;
use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\ClientAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClientPortalController extends Controller
{
    /**
     * Show the client portal settings page (agency admin)
     */
    public function settings()
    {
        $agencyId = auth()->user()->agency_id;

        $settings = Cache::remember("client_portal:{$agencyId}:settings", 600, function () use ($agencyId) {
            return ClientPortalSetting::firstOrCreate(
                ['agency_id' => $agencyId],
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
        });

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

        $agencyId = auth()->user()->agency_id;

        ClientPortalSetting::updateOrCreate(
            ['agency_id' => $agencyId],
            $validated
        );

        // Clear cache
        Cache::forget("client_portal:{$agencyId}:settings");

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

        ClientAccessToken::create([
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
    public function revokeToken(Client $client, ClientAccessToken $accessToken)
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
        $accessToken = ClientAccessToken::where('token', hash('sha256', $token))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$accessToken) {
            abort(403, 'Invalid or expired access token');
        }

        $accessToken->update(['last_used_at' => now()]);

        $client = $accessToken->client;
        $agency = $client->agency;

        $settings = Cache::remember("client_portal:{$agency->id}:{$client->id}:settings", 600, function () use ($agency) {
            return ClientPortalSetting::where('agency_id', $agency->id)->first();
        });

        // Get client's campaigns with eager loaded counts
        $campaigns = Cache::remember("client_portal:{$agency->id}:{$client->id}:campaigns", 300, function () use ($client) {
            return $client->campaigns()
                ->withCount('socialPosts')
                ->orderByDesc('created_at')
                ->paginate(10);
        });

        // Get client's invoices
        $invoices = Cache::remember("client_portal:{$agency->id}:{$client->id}:invoices", 300, function () use ($client) {
            return $client->invoices()
                ->orderByDesc('created_at')
                ->paginate(10);
        });

        // Calculate stats - optimized with single queries
        $stats = Cache::remember("client_portal:{$agency->id}:{$client->id}:stats", 300, function () use ($client) {
            $postStats = SocialPost::where('client_id', $client->id)
                ->selectRaw('
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published_posts,
                    AVG(CASE WHEN status = "published" THEN engagement_rate ELSE NULL END) as engagement_rate
                ')
                ->first();

            return [
                'total_posts' => (int) $postStats->total_posts,
                'published_posts' => (int) $postStats->published_posts,
                'engagement_rate' => round((float) ($postStats->engagement_rate ?? 0), 2),
            ];
        });

        // Get client's social posts with eager loading
        $posts = Cache::remember("client_portal:{$agency->id}:{$client->id}:posts", 300, function () use ($client) {
            return SocialPost::where('client_id', $client->id)
                ->with(['socialAccount:id,platform,platform_username,platform_display_name'])
                ->orderByDesc('created_at')
                ->take(10)
                ->get();
        });

        return view('client-portal.show', compact(
            'client', 'agency', 'settings', 'campaigns', 'invoices', 'stats', 'posts', 'token'
        ));
    }

    /**
     * Client approves a post
     */
    public function approvePost(Request $request, string $token, SocialPost $post)
    {
        $accessToken = ClientAccessToken::where('token', hash('sha256', $token))
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

        // Clear client cache
        $this->clearClientCache($accessToken->client_id);

        return response()->json(['success' => true]);
    }

    /**
     * Client rejects a post with feedback
     */
    public function rejectPost(Request $request, string $token, SocialPost $post)
    {
        $accessToken = ClientAccessToken::where('token', hash('sha256', $token))
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

        // Clear client cache
        $this->clearClientCache($accessToken->client_id);

        return response()->json(['success' => true]);
    }

    /**
     * Bulk approve posts - uses chunking for large collections
     */
    public function bulkApprovePosts(Request $request, string $token)
    {
        $validated = $request->validate([
            'post_ids' => 'required|array|max:500',
            'post_ids.*' => 'integer|exists:social_posts,id',
        ]);

        $accessToken = ClientAccessToken::where('token', hash('sha256', $token))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        $approvedCount = 0;

        // Use chunking for efficient bulk processing
        SocialPost::where('client_id', $accessToken->client_id)
            ->whereIn('id', $validated['post_ids'])
            ->where('status', '!=', 'approved')
            ->chunk(200, function ($posts) use (&$approvedCount) {
                foreach ($posts as $post) {
                    $post->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                        'approved_by' => 'client',
                    ]);
                    $approvedCount++;
                }
            });

        // Clear cache
        $this->clearClientCache($accessToken->client_id);

        return response()->json([
            'success' => true,
            'approved_count' => $approvedCount,
        ]);
    }

    /**
     * Export client data - uses cursor() for memory efficiency
     */
    public function exportData(Request $request, string $token)
    {
        $accessToken = ClientAccessToken::where('token', hash('sha256', $token))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        $client = $accessToken->client;

        // Use cursor() for memory-efficient iteration over large datasets
        $posts = SocialPost::where('client_id', $client->id)
            ->with(['socialAccount:id,platform,platform_username'])
            ->orderByDesc('created_at')
            ->cursor();

        $csvData = [];
        foreach ($posts as $post) {
            $csvData[] = [
                'id' => $post->id,
                'platform' => $post->platform,
                'status' => $post->status,
                'published_at' => $post->published_at?->toDateTimeString(),
                'engagement_rate' => $post->engagement_rate,
                'views_count' => $post->views_count,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $csvData,
        ]);
    }

    /**
     * Clear client portal caches.
     */
    private function clearClientCache(int $clientId): void
    {
        // Clear by targeted keys rather than tags for file cache compatibility
        $settingsKeys = Cache::get("client_portal_keys:{$clientId}", []);
        foreach ($settingsKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget("client_portal_keys:{$clientId}");
    }
}
