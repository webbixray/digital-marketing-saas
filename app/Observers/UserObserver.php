<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        $user->agency?->increment('users_count');
    }

    public function deleted(User $user): void
    {
        $user->agency?->decrement('users_count');
    }
}
