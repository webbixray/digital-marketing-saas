<?php

namespace App\Services\Billing;

use App\Models\Agency;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Invoice;
use Stripe\Stripe;
use Stripe\Subscription;

class StripeDunningService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function handleFailedPayment(Agency $agency, string $invoiceId): void
    {
        if (! $agency->customer_id) {
            Log::warning('No customer ID for agency', ['agency_id' => $agency->id]);

            return;
        }

        $agency->update(['subscription_status' => 'past_due']);

        $owner = $agency->users()->where('role', 'owner')->first();
        if ($owner) {
            Notification::send($owner, new PaymentFailedNotification($invoiceId));
        }

        Log::info('Payment failed notification sent', [
            'agency_id' => $agency->id,
            'invoice_id' => $invoiceId,
        ]);
    }

    public function handleSubscriptionExpired(Agency $agency): void
    {
        $agency->update([
            'subscription_plan' => 'free',
            'subscription_status' => 'cancelled',
            'subscription_id' => null,
        ]);

        $owner = $agency->users()->where('role', 'owner')->first();
        if ($owner) {
            Notification::send($owner, new SubscriptionExpiredNotification);
        }

        Log::info('Subscription expired, downgraded to free', [
            'agency_id' => $agency->id,
        ]);
    }

    public function retryPayment(Agency $agency, string $invoiceId): bool
    {
        try {
            $invoice = Invoice::retrieve($invoiceId);
            if ($invoice->status === 'open') {
                $invoice->pay();

                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to retry payment', [
                'agency_id' => $agency->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    public function getUpcomingInvoice(Agency $agency): ?array
    {
        if (! $agency->subscription_id) {
            return null;
        }

        try {
            $invoice = Invoice::upcoming([
                'customer' => $agency->customer_id,
            ]);

            return [
                'amount' => $invoice->amount_due / 100,
                'currency' => strtoupper($invoice->currency),
                'date' => date('Y-m-d', $invoice->period_end),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get upcoming invoice', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function hasActiveSubscription(Agency $agency): bool
    {
        if (! $agency->subscription_id) {
            return false;
        }

        try {
            $subscription = Subscription::retrieve($agency->subscription_id);

            return in_array($subscription->status, ['active', 'trialing']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
