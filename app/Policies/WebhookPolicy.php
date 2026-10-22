<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;

class WebhookPolicy
{
    public function view(User $user, Webhook $webhook): bool
    {
        return $user->agency_id === $webhook->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin() || $user->isEditor();
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->agency_id === $webhook->agency_id && ($user->isOwner() || $user->isAdmin() || $user->isEditor());
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->agency_id === $webhook->agency_id && ($user->isOwner() || $user->isAdmin());
    }
}
