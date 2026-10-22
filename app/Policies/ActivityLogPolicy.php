<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function view(User $user, ActivityLog $log): bool
    {
        return $user->agency_id === $log->agency_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return false; // Activity logs are system-generated, not user-creatable
    }

    public function update(User $user, ActivityLog $log): bool
    {
        return false; // Activity logs are immutable
    }

    public function delete(User $user, ActivityLog $log): bool
    {
        return $user->isOwner(); // Only owners can delete logs
    }
}
