<?php

namespace App\Observers;

use App\Events\ClientCreated;
use App\Models\Client;
use App\Services\Analytics\AnalyticsService;

class ClientObserver
{
    public function __construct(private AnalyticsService $analytics) {}

    public function created(Client $client): void
    {
        $client->agency?->increment('clients_count');
        $client->agency && $this->analytics->clearCache($client->agency);
        // event(new ClientCreated($client));
    }

    public function deleted(Client $client): void
    {
        $client->agency?->decrement('clients_count');
        $client->agency && $this->analytics->clearCache($client->agency);
    }

    public function updated(Client $client): void
    {
        if ($client->isDirty(['status', 'agency_id'])) {
            $client->agency && $this->analytics->clearCache($client->agency);
        }
    }
}
