<?php

namespace App\Observers;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Cache;

class SocialAccountObserver
{
    public function saved(SocialAccount $account): void
    {
        $this->clearCache($account->agency_id);
    }

    public function deleted(SocialAccount $account): void
    {
        $this->clearCache($account->agency_id);
    }

    public function restored(SocialAccount $account): void
    {
        $this->clearCache($account->agency_id);
    }

    private function clearCache(int $agencyId): void
    {
        $store = Cache::getStore();
        if (method_exists($store, 'tags')) {
            Cache::tags(["agency:{$agencyId}", 'social-accounts'])->flush();
        } else {
            Cache::forget("agency:{$agencyId}:social-accounts");
        }
    }
}
