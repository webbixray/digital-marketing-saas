<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->agency_id === $invoice->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->agency_id === $invoice->agency_id && $user->isEditor();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->agency_id === $invoice->agency_id && $user->isOwner();
    }
}
