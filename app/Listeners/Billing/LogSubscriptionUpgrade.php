<?php

namespace App\Listeners\Billing;

use App\Events\SubscriptionUpgraded;
use App\Models\ActivityLog;

class LogSubscriptionUpgrade
{
    public function handle(SubscriptionUpgraded $event): void
    {
        ActivityLog::create([
            'agency_id' => $event->agency->id,
            'action' => 'subscription.upgraded',
            'description' => "Plan upgraded from {$event->oldPlan} to {$event->newPlan}",
            'subject_type' => Agency::class,
            'subject_id' => $event->agency->id,
            'metadata' => ['old_plan' => $event->oldPlan, 'new_plan' => $event->newPlan],
        ]);
    }
}
