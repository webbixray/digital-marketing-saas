<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
{
    public function view(User $user, Form $form): bool
    {
        return $user->agency_id === $form->agency_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Form $form): bool
    {
        return $user->agency_id === $form->agency_id && $user->isEditor();
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->agency_id === $form->agency_id && $user->isOwner();
    }
}
