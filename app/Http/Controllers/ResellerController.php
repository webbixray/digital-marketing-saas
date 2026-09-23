<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Services\Billing\ResellerService;
use App\Services\Billing\CommissionService;
use App\Services\Billing\WhiteLabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResellerController extends Controller
{
    public function __construct(
        private ResellerService $resellerService,
        private CommissionService $commissionService,
        private WhiteLabelService $whiteLabelService
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $resellers = Reseller::byAgency($agencyId)
            ->withCount(['commissions as pending_commissions_count' => function ($q) {
                $q->where('status', \App\Models\ResellerCommission::STATUS_PENDING);
            }])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('resellers.index', compact('resellers'));
    }

    public function create()
    {
        return view('resellers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:resellers,slug',
            'domain' => 'nullable|string|max:255|unique:resellers,domain',
            'logo_url' => 'nullable|url|max:2048',
            'primary_color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|in:percentage,fixed',
            'billing_type' => 'nullable|in:monthly,revenue_share',
        ]);

        $this->resellerService->createReseller($request->user()->agency_id, $request->validated());

        return redirect()->route('resellers.index')->with('success', 'Reseller created successfully.');
    }

    public function show(int $id)
    {
        $reseller = $this->resellerService->getReseller($id);
        $agencies = $this->resellerService->getResellerAgencies($id);
        $stats = $this->resellerService->getResellerStats($id);
        $commissionSummary = $this->resellerService->getCommissionSummary($id);

        return view('resellers.show', compact('reseller', 'agencies', 'stats', 'commissionSummary'));
    }

    public function edit(int $id)
    {
        $reseller = Reseller::findOrFail($id);

        return view('resellers.edit', compact('reseller'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:resellers,slug,' . $id,
            'domain' => 'nullable|string|max:255|unique:resellers,domain,' . $id,
            'logo_url' => 'nullable|url|max:2048',
            'primary_color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|in:percentage,fixed',
            'billing_type' => 'nullable|in:monthly,revenue_share',
        ]);

        $this->resellerService->updateReseller($id, $request->validated());

        return redirect()->route('resellers.show', $id)->with('success', 'Reseller updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->resellerService->deleteReseller($id);

        return redirect()->route('resellers.index')->with('success', 'Reseller deleted successfully.');
    }

    public function commissions(Request $request, int $id)
    {
        $reseller = Reseller::findOrFail($id);
        $commissionHistory = $this->commissionService->getCommissionHistory($id);
        $pendingCommissions = $this->commissionService->getPendingCommissions($id);

        return view('resellers.commissions', compact('reseller', 'commissionHistory', 'pendingCommissions'));
    }

    public function payCommission(Request $request, int $id, int $commissionId)
    {
        $success = $this->commissionService->payCommission($commissionId);

        if ($success) {
            return back()->with('success', 'Commission marked as paid.');
        }

        return back()->with('error', 'Could not pay commission.');
    }

    public function agencies(int $id)
    {
        $reseller = Reseller::findOrFail($id);
        $agencies = $this->resellerService->getResellerAgencies($id);

        return view('resellers.agencies', compact('reseller', 'agencies'));
    }

    public function settings(int $id)
    {
        $reseller = Reseller::findOrFail($id);
        $settings = $reseller->settings ?? [];

        return view('resellers.settings', compact('reseller', 'settings'));
    }

    public function updateSettings(Request $request, int $id)
    {
        $request->validate([
            'settings' => 'nullable|array',
            'settings.brand_name' => 'nullable|string|max:255',
            'settings.brand_color' => 'nullable|string|max:7',
            'settings.logo_url' => 'nullable|url|max:2048',
            'settings.favicon_url' => 'nullable|url|max:2048',
            'settings.from_name' => 'nullable|string|max:255',
            'settings.from_email' => 'nullable|email|max:255',
            'settings.hide_powered_by' => 'boolean',
            'settings.enabled' => 'boolean',
        ]);

        $this->resellerService->updateReseller($id, ['settings' => $request->input('settings', [])]);

        return redirect()->route('resellers.settings', $id)->with('success', 'White-label settings updated.');
    }
}
