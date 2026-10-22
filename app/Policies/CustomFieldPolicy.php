<?php

namespace App\Policies;

use App\Models\CustomField;
use App\Models\User;

class CustomFieldPolicy
{
    public function view(User $user, CustomField $field): bool
    {
        return $user->agency_id === $field->agency_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, CustomField $field): bool
    {
        return $user->agency_id === $field->agency_id && $user->isEditor();
    }

    public function delete(User $user, CustomField $field): bool
    {
        return $user->agency_id === $field->agency_id && $user->isOwner();
    }
}
