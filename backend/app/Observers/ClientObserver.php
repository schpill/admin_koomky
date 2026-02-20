<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\ActivityService;
use App\Services\WebhookDispatchService;

class ClientObserver
{
    public function created(Client $client): void
    {
        ActivityService::log($client, "Client created: {$client->name}");

        // Dispatch webhook
        $this->dispatchWebhook($client, 'client.created');
    }

    public function updated(Client $client): void
    {
        ActivityService::log($client, "Client updated: {$client->name}");

        // Dispatch webhook
        $this->dispatchWebhook($client, 'client.updated');
    }

    public function deleted(Client $client): void
    {
        ActivityService::log($client, "Client deleted: {$client->name}");

        // Dispatch webhook
        $this->dispatchWebhook($client, 'client.deleted');
    }

    /**
     * Dispatch a webhook for the client event.
     *
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchWebhook(Client $client, string $event, array $extraData = []): void
    {
        $userId = $client->user_id;

        $data = array_merge([
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'reference' => $client->reference,
        ], $extraData);

        /** @var WebhookDispatchService $service */
        $service = app(WebhookDispatchService::class);
        $service->dispatch($event, $data, $userId);
    }
}
