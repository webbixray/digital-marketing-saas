<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;

class WorkflowPolicy
{
    public function view(User $user, Workflow $workflow): bool
    {
        return $user->agency_id === $workflow->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->agency_id === $workflow->agency_id;
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->agency_id === $workflow->agency_id && $user->isEditor();
    }
}
