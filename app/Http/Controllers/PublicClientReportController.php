<?php

namespace App\Http\Controllers;

use App\Models\ClientReport;
use App\Models\WhiteLabelSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicClientReportController extends Controller
{
    /**
     * Display public client report (no authentication required)
     */
    public function show(Request $request, string $slug, string $token): View
    {
        $report = ClientReport::where('slug', $slug)
            ->where('access_token', $token)
            ->where('status', 'published')
            ->firstOrFail();

        $whiteLabel = WhiteLabelSetting::where('agency_id', $report->agency_id)->first();

        return view('reports.client-public', [
            'report' => $report,
            'whiteLabel' => $whiteLabel,
            'agency' => $report->agency,
            'client' => $report->client,
        ]);
    }
}
