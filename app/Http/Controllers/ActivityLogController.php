<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $agencyId = $user->agency_id;
        $query = ActivityLog::where('agency_id', $agencyId);

        // Authorization: only owners/admins/managers can view other users' activity
        if ($request->filled('user_id')) {
            $requestedUserId = (int) $request->user_id;
            if ($requestedUserId !== $user->id && ! $user->isEditor()) {
                abort(403, 'You do not have permission to view other users\' activity.');
            }
            $query->where('user_id', $requestedUserId);
        } elseif (! $user->isEditor()) {
            // Non-editors can only see their own activity
            $query->where('user_id', $user->id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->with('user')->orderBy('created_at', 'desc')->paginate(25);

        $actions = ActivityLog::where('agency_id', $agencyId)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return view('activity.index', compact('logs', 'actions'));
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $agencyId = $user->agency_id;
        $log = ActivityLog::findOrFail($id);

        // Agency-level check
        if ((int) $log->agency_id !== (int) $agencyId) {
            abort(403);
        }

        // User-level authorization: non-editors can only view their own activity
        if ((int) $log->user_id !== (int) $user->id && ! $user->isEditor()) {
            abort(403, 'You do not have permission to view this activity log.');
        }

        return view('activity.show', compact('log'));
    }
}
