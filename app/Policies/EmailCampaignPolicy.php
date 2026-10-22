<?php

namespace App\Policies;

use App\Models\EmailCampaign;
use App\Models\User;

class EmailCampaignPolicy
{
    public function view(User $user, EmailCampaign $campaign): bool
    {
        return $user->agency_id === $campaign->agency_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmailCampaign $campaign): bool
    {
        return $user->agency_id === $campaign->agency_id && $campaign->isEditable();
    }

    public function delete(User $user, EmailCampaign $campaign): bool
    {
        return $user->agency_id === $campaign->agency_id;
    }
}
