<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->agency_id === $team->agency_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Team $team): bool
    {
        return $user->agency_id === $team->agency_id && $user->isEditor();
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->agency_id === $team->agency_id && $user->isOwner();
    }
}
