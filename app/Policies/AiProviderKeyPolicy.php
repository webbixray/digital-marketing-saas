<?php

namespace App\Policies;

use App\Models\AiProviderKey;
use App\Models\User;

class AiProviderKeyPolicy
{
    public function view(User $user, AiProviderKey $key): bool
    {
        return $user->agency_id === $key->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, AiProviderKey $key): bool
    {
        return $user->agency_id === $key->agency_id && $user->isEditor();
    }

    public function delete(User $user, AiProviderKey $key): bool
    {
        return $user->agency_id === $key->agency_id && $user->isOwner();
    }

    public function toggle(User $user, AiProviderKey $key): bool
    {
        return $user->agency_id === $key->agency_id && $user->isEditor();
    }

    public function test(User $user, AiProviderKey $key): bool
    {
        return $user->agency_id === $key->agency_id;
    }
}
