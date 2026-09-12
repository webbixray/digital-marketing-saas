<?php

namespace App\Http\Controllers;

use App\Concerns\StructuredLogger;
use App\Models\Invoice;
use App\Services\Billing\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    use StructuredLogger;

    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to load billing page', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load billing information. Please try again.');
        }
    }

    public function upgrade(Request $request)
    {
        try {
            $plans = config('stripe.plans');
            $currentPlan = $request->user()->subscription_plan ?? 'free';

            return view('billing.upgrade', compact('plans', 'currentPlan'));
        } catch (\Exception $e) {
            Log::error('Failed to load upgrade page', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load upgrade options. Please try again.');
        }
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

        try {
            $gateway = new StripeGateway;
            $session = $gateway->createCheckoutSession($agency, $plan);

            $this->logBilling('checkout_created', [
                'agency_id' => $agency->id,
                'plan' => $plan,
                'session_id' => $session->id ?? null,
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            $this->logBillingError('checkout_failed', [
                'agency_id' => $agency->id,
                'plan' => $plan,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Could not create checkout session. Please try again.');
        }
    }

    public function success(Request $request)
    {
        try {
            return view('billing.success');
        } catch (\Exception $e) {
            Log::error('Failed to load success page', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')->with('error', 'An error occurred.');
        }
    }

    public function cancel(Request $request)
    {
        return redirect()->route('agency.billing')
            ->with('warning', 'Checkout was canceled.');
    }

    public function invoices(Request $request)
    {
        try {
            $agency = $request->user()->agency;
            $invoices = Invoice::where('agency_id', $agency->id)
                ->with('client')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return view('billing.invoices', compact('agency', 'invoices'));
        } catch (\Exception $e) {
            Log::error('Failed to load invoices', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load invoices. Please try again.');
        }
    }

    public function downloadInvoice(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency_id;
        if ((int) $invoice->agency_id !== (int) $agency) {
            abort(403);
        }

        try {
            return redirect()->route('agency.invoices')
                ->with('info', 'Invoice download coming soon.');
        } catch (\Exception $e) {
            Log::error('Failed to download invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to download invoice. Please try again.');
        }
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $gateway = new StripeGateway;
            $gateway->handleWebhook($payload, $sigHeader);

            Log::info('Stripe webhook processed successfully');

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            $this->logBillingError('webhook_failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function cancelSubscription(Request $request)
    {
        $agency = $request->user()->agency;

        try {
            $gateway = new StripeGateway;
            $gateway->cancelSubscription($agency);

            $this->logBilling('subscription_cancelled', [
                'agency_id' => $agency->id,
            ]);

            return back()->with('success', 'Subscription canceled.');
        } catch (\Exception $e) {
            $this->logBillingError('cancel_failed', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Could not cancel subscription. Please contact support.');
        }
    }
}
