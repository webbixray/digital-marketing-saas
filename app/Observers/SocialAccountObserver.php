<?php

namespace App\Observers;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Cache;

class SocialAccountObserver
{
    public function saved(SocialAccount $account): void
    {
        Cache::tags(["agency:{$account->agency_id}", 'social-accounts'])->flush();
    }

    public function deleted(SocialAccount $account): void
    {
        Cache::tags(["agency:{$account->agency_id}", 'social-accounts'])->flush();
    }

    public function updated(SocialAccount $account): void
    {
        if ($account->isDirty(['is_connected', 'status'])) {
            Cache::tags(["agency:{$account->agency_id}", 'social-accounts'])->flush();
        }
    }
}
