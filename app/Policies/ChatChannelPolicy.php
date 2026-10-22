<?php

namespace App\Policies;

use App\Models\ChatChannel;
use App\Models\User;

class ChatChannelPolicy
{
    public function view(User $user, ChatChannel $channel): bool
    {
        return $user->agency_id === $channel->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, ChatChannel $channel): bool
    {
        return $user->agency_id === $channel->agency_id && $user->isEditor();
    }

    public function delete(User $user, ChatChannel $channel): bool
    {
        return $user->agency_id === $channel->agency_id && $user->isOwner();
    }
}
