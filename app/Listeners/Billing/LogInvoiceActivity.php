<?php

namespace App\Listeners\Billing;

use App\Events\InvoicePaid;
use App\Models\ActivityLog;

class LogInvoiceActivity
{
    public function handle(InvoicePaid $event): void
    {
        ActivityLog::create([
            'agency_id' => $event->invoice->agency_id,
            'action' => 'invoice.paid',
            'description' => "Invoice {$event->invoice->invoice_number} marked as paid via {$event->paymentMethod}",
            'subject_type' => Invoice::class,
            'subject_id' => $event->invoice->id,
            'metadata' => ['total' => $event->invoice->total, 'method' => $event->paymentMethod],
        ]);
    }
}
