<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AiCreditPurchase;
use App\Services\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class AICreditController extends Controller
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {}

    /**
     * Get current credit balance for the agency
     */
    public function balance(Request $request): JsonResponse
    {
        /** @var Agency $agency */
        $agency = $request->user()->agency;

        return response()->json([
            'credits' => (int) $agency->ai_credits,
            'total_purchased' => (int) $agency->ai_credits_purchased,
        ]);
    }

    /**
     * Create a Stripe checkout session for purchasing AI credits
     */
    public function purchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credits' => 'required|integer|in:100,500,1000,5000]',
        ]);

        $credits = (int) $validated['credits'];
        $price = $this->calculatePrice($credits);

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => "{$credits} AI Credits",
                        'description' => 'Additional AI content generations for your agency.',
                    ],
                    'unit_amount' => (int) round($price * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('agency.billing').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('agency.billing'),
            'metadata' => [
                'agency_id' => $request->user()->agency_id,
                'credits' => $credits,
            ],
        ]);

        return response()->json([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'credits' => $credits,
            'price' => $price,
        ]);
    }

    /**
     * Handle successful credit purchase
     */
    public function success(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return response()->json(['error' => 'Session ID is required.'], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $session = Session::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            return response()->json(['error' => 'Payment not completed.'], 400);
        }

        /** @var Agency $agency */
        $agency = Agency::find($session->metadata->agency_id);
        $credits = (int) $session->metadata->credits;

        if (! $agency) {
            return response()->json(['error' => 'Agency not found.'], 404);
        }

        // Add credits to agency
        $this->quotaService->addAiCredits($agency, $credits);

        // Log purchase
        AiCreditPurchase::create([
            'agency_id' => $agency->id,
            'credits' => $credits,
            'amount' => $session->amount_total / 100,
            'stripe_payment_id' => $session->payment_intent,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'credits_added' => $credits,
            'total_credits' => (int) $agency->ai_credits,
        ]);
    }

    /**
     * Calculate price for credits with bulk discounts
     */
    private function calculatePrice(int $credits): float
    {
        $basePrice = 0.09;

        $discount = match (true) {
            $credits >= 5000 => 0.30,
            $credits >= 1000 => 0.20,
            $credits >= 500 => 0.10,
            default => 0,
        };

        return round($credits * $basePrice * (1 - $discount), 2);
    }
}
