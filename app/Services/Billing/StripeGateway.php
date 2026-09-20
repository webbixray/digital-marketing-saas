<?php

namespace App\Services\Billing;

use App\Models\Agency;
use App\Models\Invoice;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;
use Stripe\InvoiceItem;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;

class StripeGateway
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create or retrieve Stripe customer for agency.
     */
    public function getOrCreateCustomer(Agency $agency): string
    {
        if ($agency->customer_id) {
            return $agency->customer_id;
        }

        $customer = Customer::create([
            'email' => $agency->email,
            'name' => $agency->name,
            'metadata' => ['agency_id' => $agency->id],
        ]);

        $agency->update(['customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * Create checkout session for plan upgrade.
     */
    public function createCheckoutSession(Agency $agency, string $plan, string $billing = 'month'): CheckoutSession
    {
        $priceId = config("services.stripe.plans.{$plan}.{$billing}");

        if (! $priceId) {
            throw new \RuntimeException("Stripe price ID not found for plan: {$plan}/{$billing}");
        }

        $customerId = $this->getOrCreateCustomer($agency);

        return CheckoutSession::create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => route('agency.billing').'?checkout=success',
            'cancel_url' => route('agency.billing').'?checkout=canceled',
            'metadata' => [
                'agency_id' => $agency->id,
                'plan' => $plan,
            ],
        ]);
    }

    /**
     * Create invoice for agency (one-time or overage).
     */
    public function createInvoice(Invoice $invoice): void
    {
        if (! $invoice->agency->customer_id) {
            throw new \RuntimeException('No Stripe customer ID for agency.');
        }

        InvoiceItem::create([
            'customer' => $invoice->agency->customer_id,
            'amount' => (int) ($invoice->total * 100), // cents
            'currency' => strtolower($invoice->currency),
            'description' => "Invoice {$invoice->invoice_number}",
        ]);

        \Stripe\Invoice::create([
            'customer' => $invoice->agency->customer_id,
            'auto_advance' => true,
            'metadata' => [
                'agency_id' => $invoice->agency_id,
                'invoice_id' => $invoice->id,
            ],
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Agency $agency): void
    {
        if (! $agency->subscription_id) {
            throw new \RuntimeException('No active subscription.');
        }

        Subscription::retrieve($agency->subscription_id)->cancel();
    }

    /**
     * Handle a verified webhook event (signature already validated by controller).
     */
    public function handleWebhookEvent(\Stripe\Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.created' => $this->handleSubscriptionCreated($event->data->object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.paid' => $this->handleInvoicePaid($event->data->object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object),
            default => null,
        };
    }

    /**
     * Handle webhook event.
     * 
     * @deprecated Use handleWebhookEvent() after verifying signature in controller
     */
    public function handleWebhook(string $payload, string $sigHeader): void
    {
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            abort(400, 'Invalid webhook signature');
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.created' => $this->handleSubscriptionCreated($event->data->object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.paid' => $this->handleInvoicePaid($event->data->object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object),
            default => null,
        };
    }

    protected function handleCheckoutCompleted($session): void
    {
        $agency = Agency::findOrFail($session->metadata->agency_id);
        $agency->update([
            'subscription_plan' => $session->metadata->plan,
            'subscription_id' => $session->subscription,
            'subscription_status' => 'active',
            'subscription_start' => now(),
        ]);
    }

    protected function handleSubscriptionCreated($subscription): void
    {
        $agency = Agency::where('customer_id', $subscription->customer)->firstOrFail();
        $agency->update([
            'subscription_id' => $subscription->id,
            'subscription_status' => 'active',
            'subscription_start' => now(),
        ]);
    }

    protected function handleSubscriptionUpdated($subscription): void
    {
        $agency = Agency::where('customer_id', $subscription->customer)->firstOrFail();
        $agency->update([
            'subscription_status' => $subscription->status === 'active' ? 'active' : 'past_due',
        ]);
    }

    protected function handleSubscriptionDeleted($subscription): void
    {
        $agency = Agency::where('customer_id', $subscription->customer)->firstOrFail();
        $agency->update([
            'subscription_plan' => 'free',
            'subscription_status' => 'cancelled',
            'subscription_id' => null,
        ]);
    }

    protected function handleInvoicePaid($invoice): void
    {
        // Mark our local invoice as paid
        $localInvoice = Invoice::find($invoice->metadata->invoice_id ?? null);
        if ($localInvoice) {
            $localInvoice->markPaid('stripe', $invoice->id);
        }
    }

    protected function handleInvoicePaymentFailed($invoice): void
    {
        $localInvoice = Invoice::find($invoice->metadata->invoice_id ?? null);
        if ($localInvoice) {
            $localInvoice->update(['status' => 'overdue']);
        }
    }
}
