<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;

class TeamActivityController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display team activity feed.
     */
    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $perPage = $request->input('per_page', 25);

        $logs = $this->auditLog->getAuditTrail($agencyId, $perPage);
        $stats = $this->auditLog->getActionStats($agencyId, 7);

        return view('activity.index', compact('logs', 'stats'));
    }
}
