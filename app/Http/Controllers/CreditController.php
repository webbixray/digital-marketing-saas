<?php

namespace App\Http\Controllers;

use App\Services\Billing\CreditService;
use App\Services\Billing\MeteredBillingService;
use App\Services\Billing\UsageQuotaService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class CreditController extends Controller
{
    use HandlesErrors;

    protected CreditService $creditService;
    protected MeteredBillingService $meteredBillingService;
    protected UsageQuotaService $usageQuotaService;

    public function __construct(
        CreditService $creditService,
        MeteredBillingService $meteredBillingService,
        UsageQuotaService $usageQuotaService
    ) {
        $this->middleware(['auth', 'agency']);
        $this->creditService = $creditService;
        $this->meteredBillingService = $meteredBillingService;
        $this->usageQuotaService = $usageQuotaService;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $balance = $this->creditService->getBalance($agencyId);
        $transactions = $this->creditService->getTransactionHistory($agencyId);
        $summary = $this->creditService->getCreditSummary($agencyId);
        $monthlySpend = $this->creditService->getMonthlySpend($agencyId);

        return view('billing.credits', compact(
            'balance',
            'transactions',
            'summary',
            'monthlySpend'
        ));
    }

    public function purchase(Request $request): JsonResponse
    {
        return $this->handleAction(function () use ($request) {
            $request->validate([
                'amount' => 'required|integer|min:100',
            ]);

            $user = $request->user();
            $agencyId = $user->agency_id;
            $amount = (int) $request->input('amount');
            $description = $request->input('description', 'Credit purchase');

            $transaction = $this->creditService->addCredits(
                $agencyId,
                $amount,
                $description,
                $user->id,
                ['source' => 'web_purchase']
            );

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'new_balance' => $this->creditService->getBalance($agencyId),
            ]);
        }, 'Could not process credit purchase.');
    }

    public function usage(Request $request): View
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $usageSummary = $this->meteredBillingService->getUsageSummary($agencyId);
        $billItems = $this->meteredBillingService->getBillItems($agencyId);
        $currentBill = $this->meteredBillingService->calculateBill($agencyId);

        return view('billing.credits', compact(
            'usageSummary',
            'billItems',
            'currentBill'
        ));
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $creditSummary = $this->creditService->getCreditSummary($agencyId);
        $usageSummary = $this->meteredBillingService->getUsageSummary($agencyId);
        $quotaSummary = $this->usageQuotaService->getQuotaSummary($agencyId);

        return response()->json([
            'credits' => $creditSummary,
            'usage' => $usageSummary,
            'quotas' => $quotaSummary,
        ]);
    }
}
