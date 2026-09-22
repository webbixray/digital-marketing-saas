<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Models\OptimalPostingTime;
use App\Services\Calendar\ContentCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        private readonly ContentCalendarService $calendarService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display the unified content calendar view.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $stats = $this->calendarService->getStats(
            $agencyId,
            now()->startOfMonth()->toDateTimeString(),
            now()->endOfMonth()->toDateTimeString()
        );

        $bestTimes = $this->calendarService->getBestPostingTimes($agencyId);
        $optimalSlots = $this->calendarService->suggestOptimalSlots($agencyId);

        $platforms = SocialPost::where('agency_id', $agencyId)
            ->distinct()
            ->pluck('platform')
            ->filter()
            ->values();

        $accounts = \App\Models\SocialAccount::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->get(['id', 'platform', 'platform_username', 'platform_display_name']);

        return view('calendar.index', compact(
            'stats',
            'bestTimes',
            'optimalSlots',
            'platforms',
            'accounts'
        ));
    }

    /**
     * Return JSON events for FullCalendar.
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
}
