<?php

namespace App\Http\Controllers;

use App\Models\ActivityFeed;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as FacadeView;

class ActivityFeedController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): ViewContract|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $agencyId = $user->agency_id;
        $query = ActivityFeed::where('agency_id', $agencyId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $activities = $query->paginate(20);

        return FacadeView::make('activity-feed.index', compact('activities'));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'action' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'subject_type' => 'nullable|string|max:255',
            'subject_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        ActivityFeed::create([
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
            ...$validated,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $activity = DB::table('activity_feed')->where('id', $id)->first();

        if (! $activity || (int) $activity->agency_id !== (int) $user->agency_id) {
            abort(403);
        }

        ActivityFeed::where('id', $id)->delete();

        return response()->json(['success' => true]);
    }
}
