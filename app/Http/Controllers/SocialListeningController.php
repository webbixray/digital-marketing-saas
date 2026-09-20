<?php

namespace App\Http\Controllers;

use App\Models\SocialListening;
use App\Services\Social\SocialListeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SocialListeningController extends Controller
{
    protected SocialListeningService $service;

    public function __construct(SocialListeningService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the social listening dashboard.
     */
    public function index(): View
    {
        $agencyId = Auth::user()->agency_id;

        $stats = $this->service->getDashboardStats($agencyId);
        $keywords = $this->service->getKeywords($agencyId);
        $mentionFeed = $this->service->getMentionFeed($agencyId);

        return view('social-listening.dashboard', compact('stats', 'keywords', 'mentionFeed'));
    }

    /**
     * Show the form for creating a new keyword.
     */
    public function create(): View
    {
        return view('social-listening.create');
    }

    /**
     * Store a newly created keyword.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'platform' => 'required|string|in:all,twitter,facebook,instagram,linkedin,tiktok',
        ]);

        $this->service->addKeyword(
            agencyId: Auth::user()->agency_id,
            keyword: $validated['keyword'],
            platform: $validated['platform']
        );

        return redirect()->route('social-listening.index')
            ->with('success', 'Keyword added to monitoring successfully.');
    }

    /**
     * Display the specified keyword details.
     */
    public function show(SocialListening $socialListening): View
    {
        $this->authorize('view', $socialListening);

        return view('social-listening.show', compact('socialListening'));
    }

    /**
     * Show the form for editing the specified keyword.
     */
    public function edit(SocialListening $socialListening): View
    {
        $this->authorize('update', $socialListening);

        return view('social-listening.edit', compact('socialListening'));
    }

    /**
     * Update the specified keyword.
     */
    public function update(Request $request, SocialListening $socialListening): RedirectResponse
    {
        $this->authorize('update', $socialListening);

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'platform' => 'required|string|in:all,twitter,facebook,instagram,linkedin,tiktok',
            'is_active' => 'boolean',
        ]);

        $socialListening->update([
            'keyword' => strtolower(trim($validated['keyword'])),
            'platform' => $validated['platform'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('social-listening.index')
            ->with('success', 'Keyword updated successfully.');
    }

    /**
     * Remove the specified keyword.
     */
    public function destroy(SocialListening $socialListening): RedirectResponse
    {
        $this->authorize('delete', $socialListening);

        $this->service->removeKeyword(Auth::user()->agency_id, $socialListening->id);

        return redirect()->route('social-listening.index')
            ->with('success', 'Keyword removed from monitoring.');
    }

    /**
     * Toggle keyword active status.
     */
    public function toggle(SocialListening $socialListening): JsonResponse
    {
        $this->authorize('update', $socialListening);

        $socialListening->update(['is_active' => !$socialListening->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $socialListening->is_active,
        ]);
    }

    /**
     * Run mention check for all keywords.
     */
    public function refresh(): RedirectResponse
    {
        $agencyId = Auth::user()->agency_id;
        $result = $this->service->checkMentions($agencyId);

        return redirect()->route('social-listening.index')
            ->with('success', "Checked {$result['total_keywords']} keywords, found {$result['total_new_mentions']} new mentions.");
    }

    /**
     * Get dashboard stats as JSON (for AJAX refresh).
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getDashboardStats(Auth::user()->agency_id);

        return response()->json($stats);
    }

    /**
     * Get mention feed as JSON (for AJAX refresh).
     */
    public function feed(): JsonResponse
    {
        $feed = $this->service->getMentionFeed(Auth::user()->agency_id);

        return response()->json($feed);
    }
}
