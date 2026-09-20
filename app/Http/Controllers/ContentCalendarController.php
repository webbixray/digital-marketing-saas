<?php

namespace App\Http\Controllers;

use App\Services\Calendar\ContentCalendarService;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentCalendarController extends Controller
{
    public function __construct(
        private readonly ContentCalendarService $calendarService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display the interactive content calendar.
     */
    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $stats = $this->calendarService->getStats(
            $agencyId,
            now()->startOfMonth()->toDateTimeString(),
            now()->endOfMonth()->toDateTimeString()
        );
        $bestTimes = $this->calendarService->getBestPostingTimes($agencyId);

        // Get platforms and accounts for filters
        $platforms = SocialPost::where('agency_id', $agencyId)
            ->distinct()
            ->pluck('platform')
            ->filter()
            ->values();

        $accounts = \App\Models\SocialAccount::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->get(['id', 'platform', 'platform_username', 'platform_display_name']);

        return view('calendar.index', compact('stats', 'bestTimes', 'year', 'month', 'platforms', 'accounts'));
    }

    /**
     * Get events for FullCalendar JSON feed.
     */
    public function events(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        $platform = $request->input('platform');
        $accountId = $request->input('account_id');

        $events = $this->calendarService->getEvents($agencyId, $start, $end, $platform, $accountId);

        return response()->json($events);
    }

    /**
     * Update post schedule via drag-drop AJAX.
     */
    public function updateSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:social_posts,id',
            'scheduled_at' => 'required|date',
        ]);

        $post = SocialPost::where('agency_id', $request->user()->agency_id)
            ->findOrFail($validated['id']);

        $post->update([
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post rescheduled successfully.',
            'post' => [
                'id' => $post->id,
                'scheduled_at' => $post->scheduled_at->toISOString(),
            ],
        ]);
    }
}
