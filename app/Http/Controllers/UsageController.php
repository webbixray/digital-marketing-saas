<?php

namespace App\Http\Controllers;

use App\Services\Billing\CreditService;
use App\Services\Billing\MeteredBillingService;
use App\Services\Billing\UsageQuotaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsageController extends Controller
{
    use HandlesErrors;

    protected MeteredBillingService $meteredBillingService;
    protected UsageQuotaService $usageQuotaService;
    protected CreditService $creditService;

    public function __construct(
        MeteredBillingService $meteredBillingService,
        UsageQuotaService $usageQuotaService,
        CreditService $creditService
    ) {
        $this->middleware(['auth', 'agency']);
        $this->meteredBillingService = $meteredBillingService;
        $this->usageQuotaService = $usageQuotaService;
        $this->creditService = $creditService;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $usageSummary = $this->meteredBillingService->getUsageSummary($agencyId);
        $billItems = $this->meteredBillingService->getBillItems($agencyId);
        $currentBill = $this->meteredBillingService->calculateBill($agencyId);
        $quotaStatus = $this->meteredBillingService->getQuotaStatus($agencyId);
        $monthlySpend = $this->creditService->getMonthlySpend($agencyId);

        return view('billing.usage', compact(
            'usageSummary',
            'billItems',
            'currentBill',
            'quotaStatus',
            'monthlySpend'
        ));
    }

    public function quota(Request $request): View
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $quotaSummary = $this->usageQuotaService->getQuotaSummary($agencyId);
        $exceededQuotas = $this->usageQuotaService->getExceededQuotas($agencyId);

        return view('billing.usage', compact(
            'quotaSummary',
            'exceededQuotas'
        ));
    }

    public function metered(Request $request): View
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $usageSummary = $this->meteredBillingService->getUsageSummary($agencyId);
        $billItems = $this->meteredBillingService->getBillItems($agencyId);
        $currentBill = $this->meteredBillingService->calculateBill($agencyId);

        return view('billing.usage', compact(
            'usageSummary',
            'billItems',
            'currentBill'
        ));
    }
}
