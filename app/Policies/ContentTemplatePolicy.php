<?php

namespace App\Policies;

use App\Models\ContentTemplate;
use App\Models\User;

class ContentTemplatePolicy
{
    public function view(User $user, ContentTemplate $template): bool
    {
        return $user->agency_id === $template->agency_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, ContentTemplate $template): bool
    {
        return $user->agency_id === $template->agency_id && $user->isEditor();
    }

    public function delete(User $user, ContentTemplate $template): bool
    {
        return $user->agency_id === $template->agency_id && $user->isOwner();
    }
}
