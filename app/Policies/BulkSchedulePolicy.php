<?php

namespace App\Policies;

use App\Models\BulkSchedule;
use App\Models\User;

class BulkSchedulePolicy
{
    public function view(User $user, BulkSchedule $bulkSchedule): bool
    {
        // Must be in same agency
        if ((int) $user->agency_id !== (int) $bulkSchedule->agency_id) {
            return false;
        }

        // Users can view their own; editors can view any
        if ((int) $user->id === (int) $bulkSchedule->user_id) {
            return true;
        }

        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }
}
