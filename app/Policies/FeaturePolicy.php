<?php

namespace App\Policies;

use App\Models\Feature;
use App\Models\User;

class FeaturePolicy
{
    public function view(User $user, Feature $feature): bool
    {
        return $user->isEditor();
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Feature $feature): bool
    {
        return $user->isEditor();
    }

    public function delete(User $user, Feature $feature): bool
    {
        return $user->isOwner();
    }
}
