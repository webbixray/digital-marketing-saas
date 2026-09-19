<?php

namespace App\Http\Controllers;

use App\Models\WhiteLabelSetting;
use App\Services\WhiteLabel\WhiteLabelService;
use Illuminate\Http\Request;

class WhiteLabelController extends Controller
{
    public function __construct(private WhiteLabelService $whiteLabelService)
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $settings = WhiteLabelSetting::where('agency_id', $agencyId)->first();
        $brandedAssets = $this->whiteLabelService->getBrandedAssets($agencyId);

        return view('white-label.index', compact('agencyId', 'settings', 'brandedAssets'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'brand_name' => 'nullable|string|max:255',
            'brand_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo_url' => 'nullable|url|max:2048',
            'favicon_url' => 'nullable|url|max:2048',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'custom_css' => 'nullable|string|max:10000',
            'email_signature' => 'nullable|string|max:5000',
            'hide_powered_by' => 'boolean',
            'enabled' => 'boolean',
        ]);

        $agencyId = $request->user()->agency_id;

        WhiteLabelSetting::updateOrCreate(
            ['agency_id' => $agencyId],
            $request->only([
                'brand_name',
                'brand_color',
                'logo_url',
                'favicon_url',
                'from_name',
                'from_email',
                'custom_css',
                'email_signature',
                'hide_powered_by',
                'enabled',
            ])
        );

        return back()->with('success', 'White-label settings updated.');
    }

    public function setupDomain(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
        ]);

        $agencyId = $request->user()->agency_id;
        $domain = $request->input('domain');

        $success = $this->whiteLabelService->setupCustomDomain($agencyId, $domain);

        if (! $success) {
            return back()->with('error', 'Domain is already in use or could not be configured.');
        }

        return back()->with('success', 'Custom domain configured successfully.');
    }

    public function validateDomain(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
        ]);

        $domain = $request->input('domain');
        $isValid = $this->whiteLabelService->validateDomainOwnership($domain);

        return response()->json([
            'valid' => $isValid,
            'domain' => $domain,
        ]);
    }
}
