<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function view(User $user, Campaign $campaign): bool
    {
        return $user->agency_id === $campaign->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->agency_id === $campaign->agency_id;
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->agency_id === $campaign->agency_id && $user->isEditor();
    }
}
