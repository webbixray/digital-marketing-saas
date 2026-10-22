<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\Analytics\AnalyticsService;

class InvoiceObserver
{
    public function __construct(private AnalyticsService $analytics) {}

    public function created(Invoice $invoice): void
    {
        $invoice->agency && $this->analytics->clearCache($invoice->agency);
    }

    public function deleted(Invoice $invoice): void
    {
        $invoice->agency && $this->analytics->clearCache($invoice->agency);
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty(['status', 'total', 'paid_at', 'agency_id'])) {
            $invoice->agency && $this->analytics->clearCache($invoice->agency);
        }
    }
}
