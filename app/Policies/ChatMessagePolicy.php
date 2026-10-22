<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    public function view(User $user, ChatMessage $message): bool
    {
        return $user->agency_id === $message->channel->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, ChatMessage $message): bool
    {
        return $user->agency_id === $message->channel->agency_id
            && $user->isEditor()
            && $message->user_id === $user->id;
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        return $user->agency_id === $message->channel->agency_id
            && ($user->isOwner() || $message->user_id === $user->id);
    }
}
