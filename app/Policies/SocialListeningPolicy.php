<?php

namespace App\Policies;

use App\Models\SocialListening;
use App\Models\User;

class SocialListeningPolicy
{
    public function view(User $user, SocialListening $listening): bool
    {
        return $user->agency_id === $listening->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, SocialListening $listening): bool
    {
        return $user->agency_id === $listening->agency_id && $user->isEditor();
    }

    public function delete(User $user, SocialListening $listening): bool
    {
        return $user->agency_id === $listening->agency_id && $user->isEditor();
    }
}
