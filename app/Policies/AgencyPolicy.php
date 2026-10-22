<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
    public function view(User $user, Agency $agency): bool
    {
        return $user->agency_id === $agency->id;
    }

    public function update(User $user, Agency $agency): bool
    {
        return $user->agency_id === $agency->id && ($user->isOwner() || $user->isAdmin());
    }
}
