<?php

namespace App\Http\Controllers;

use App\Services\PerformanceService;

class SystemStatusController extends Controller
{
    public function __construct(
        private readonly PerformanceService $performance,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display system status dashboard.
     */
    public function index()
    {
        $metrics = $this->performance->getAllMetrics();

        return view('system.status', compact('metrics'));
    }
}
