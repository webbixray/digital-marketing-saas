<?php

namespace App\Policies;

use App\Models\LandingPage;
use App\Models\User;

class LandingPagePolicy
{
    public function view(User $user, LandingPage $page): bool
    {
        return $user->agency_id === $page->agency_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, LandingPage $page): bool
    {
        return $user->agency_id === $page->agency_id && $user->isEditor();
    }

    public function delete(User $user, LandingPage $page): bool
    {
        return $user->agency_id === $page->agency_id && $user->isOwner();
    }
}
