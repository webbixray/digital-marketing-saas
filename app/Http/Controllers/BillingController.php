<?php

namespace App\Http\Controllers;

use App\Concerns\StructuredLogger;
use App\Models\Invoice;
use App\Services\Billing\StripeGateway;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    use HandlesErrors, StructuredLogger;

    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;
        $agencyId = $agency->id;
        $invoices = Invoice::where('agency_id', $agencyId)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $plans = config('stripe.plans');
        $currentPlan = $agency->subscription_plan ?? 'free';

        return view('billing.index', compact('agency', 'invoices', 'plans', 'currentPlan'));
    }

    public function upgrade(Request $request)
    {
        $plans = config('stripe.plans');
        $currentPlan = $request->user()->subscription_plan ?? 'free';

        return view('billing.upgrade', compact('plans', 'currentPlan'));
    }

    public function checkout(Request $request, string $plan)
    {
        $agency = $request->user()->agency;

        if ($plan === 'free') {
            return back()->with('error', 'Free plan does not require checkout.');
        }

        if (! config("stripe.plans.{$plan}.monthly")) {
            return back()->with('error', 'Invalid plan selected.');
        }

        return $this->handleAction(function () use ($agency, $plan) {
            $gateway = new StripeGateway;
            $session = $gateway->createCheckoutSession($agency, $plan);

            $this->logBilling('checkout_created', [
                'agency_id' => $agency->id,
                'plan' => $plan,
                'session_id' => $session->id ?? null,
            ]);

            return redirect($session->url);
        }, 'Could not create checkout session. Please try again.', [
            'route' => 'agency.billing',
            'message' => 'Could not create checkout session. Please try again.',
        ]);
    }

    public function success(Request $request)
    {
        // Award referral credits if user was referred
        $user = $request->user();
        if ($user && $user->referred_by) {
            $referralService = app(ReferralService::class);
            $referralService->awardReferralCredits($user);
        }

        return view('billing.success');
    }

    public function cancel(Request $request)
    {
        return redirect()->route('agency.billing')
            ->with('warning', 'Checkout was canceled.');
    }

    public function invoices(Request $request)
    {
        $agency = $request->user()->agency;
        $invoices = Invoice::where('agency_id', $agency->id)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('billing.invoices', compact('agency', 'invoices'));
    }

    public function downloadInvoice(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency_id;
        if ((int) $invoice->agency_id !== (int) $agency) {
            abort(403);
        }

        return redirect()->route('agency.invoices')
            ->with('info', 'Invoice download coming soon.');
    }

    public function webhook(Request $request)
    {
        // Ensure content type is JSON
        if (! $request->isJson()) {
            return response()->json(['error' => 'Invalid content type.'], 415);
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        return $this->handleApiAction(function () use ($payload, $sigHeader) {
            $gateway = new StripeGateway;
            $gateway->handleWebhook($payload, $sigHeader);

            Log::info('Stripe webhook processed successfully');

            return response()->json(['status' => 'ok']);
        }, 'Webhook processing failed.');
    }

    public function cancelSubscription(Request $request)
    {
        $agency = $request->user()->agency;

        return $this->handleAction(function () use ($agency) {
            $gateway = new StripeGateway;
            $gateway->cancelSubscription($agency);

            $this->logBilling('subscription_cancelled', [
                'agency_id' => $agency->id,
            ]);

            return back()->with('success', 'Subscription canceled.');
        }, 'Could not cancel subscription. Please contact support.', [
            'route' => 'agency.billing',
            'message' => 'Could not cancel subscription. Please contact support.',
        ]);
    }
}
