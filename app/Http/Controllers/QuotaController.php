<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Services\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotaController extends Controller
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {}

    /**
     * Get current quota status for the agency
     */
    public function status(Request $request): JsonResponse
    {
        /** @var Agency $agency */
        $agency = $request->user()->agency;

        return response()->json([
            'plan' => $agency->subscription_plan,
            'quotas' => $this->quotaService->getQuotaStatus($agency),
            'upgrade_available' => $agency->subscription_plan === 'free',
        ]);
    }

    /**
     * Check if a specific action is allowed
     */
    public function check(Request $request): JsonResponse
    {
        $feature = $request->input('feature');

        if (! $feature) {
            return response()->json(['error' => 'Feature is required.'], 422);
        }

        /** @var Agency $agency */
        $agency = $request->user()->agency;

        $isOverQuota = $this->quotaService->isOverQuota($agency, $feature);

        return response()->json([
            'allowed' => ! $isOverQuota,
            'feature' => $feature,
            'usage' => $this->quotaService->getUsage($agency, $feature),
            'limit' => $this->quotaService->getLimit($agency, $feature),
        ]);
    }
}
