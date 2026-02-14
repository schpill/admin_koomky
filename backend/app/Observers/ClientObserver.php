<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\ActivityService;

class ClientObserver
{
    public function created(Client $client): void
    {
        ActivityService::log($client, "Client created: {$client->name}");
    }

    public function updated(Client $client): void
    {
        ActivityService::log($client, "Client updated: {$client->name}");
    }

    public function deleted(Client $client): void
    {
        ActivityService::log($client, "Client deleted: {$client->name}");
    }
}
