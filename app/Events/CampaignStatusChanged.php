<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public string $oldStatus,
        public string $newStatus,
    ) {}
}
