<?php

namespace App\Policies;

use App\Models\SocialPost;
use App\Models\User;

class SocialPostPolicy
{
    public function view(User $user, SocialPost $post): bool
    {
        return $user->agency_id === $post->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, SocialPost $post): bool
    {
        return $user->agency_id === $post->agency_id;
    }

    public function delete(User $user, SocialPost $post): bool
    {
        return $user->agency_id === $post->agency_id;
    }
}
