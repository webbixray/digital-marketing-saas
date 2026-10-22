<?php

namespace App\Policies;

use App\Models\ClientReport;
use App\Models\User;

class ClientReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function view(User $user, ClientReport $clientReport): bool
    {
        return $user->agency_id === $clientReport->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function update(User $user, ClientReport $clientReport): bool
    {
        return $user->agency_id === $clientReport->agency_id;
    }

    public function delete(User $user, ClientReport $clientReport): bool
    {
        return $user->agency_id === $clientReport->agency_id;
    }

    public function restore(User $user, ClientReport $clientReport): bool
    {
        return $user->agency_id === $clientReport->agency_id;
    }

    public function forceDelete(User $user, ClientReport $clientReport): bool
    {
        return $user->agency_id === $clientReport->agency_id;
    }
}
