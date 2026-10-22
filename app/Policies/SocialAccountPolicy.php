<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;

class SocialAccountPolicy
{
    public function view(User $user, SocialAccount $account): bool
    {
        return $user->agency_id === $account->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, SocialAccount $account): bool
    {
        return $user->agency_id === $account->agency_id && $user->isEditor();
    }

    public function delete(User $user, SocialAccount $account): bool
    {
        return $user->agency_id === $account->agency_id && $user->isEditor();
    }
}
