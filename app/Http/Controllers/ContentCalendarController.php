<?php

namespace App\Http\Controllers;

use App\Services\Calendar\ContentCalendarService;
use Illuminate\Http\Request;

class ContentCalendarController extends Controller
{
    public function __construct(
        private readonly ContentCalendarService $calendarService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display the content calendar.
     */
    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $events = $this->calendarService->getMonthEvents($agencyId, $year, $month);
        $stats = $this->calendarService->getStats(
            $agencyId,
            now()->startOfMonth()->toDateTimeString(),
            now()->endOfMonth()->toDateTimeString()
        );
        $bestTimes = $this->calendarService->getBestPostingTimes($agencyId);

        return view('calendar.index', compact('events', 'stats', 'bestTimes', 'year', 'month'));
    }

    /**
     * Get events for a date range (API).
     */
    public function events(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        $events = $this->calendarService->getEvents($agencyId, $start, $end);

        return response()->json($events);
    }
}
