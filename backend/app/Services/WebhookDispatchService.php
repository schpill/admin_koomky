<?php

namespace App\Services;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Service for dispatching webhooks to external endpoints.
 */
class WebhookDispatchService
{
    /**
     * Dispatch a webhook event to all subscribed endpoints.
     *
     * @param  string  $event  The event name (e.g., 'invoice.created')
     * @param  array<string, mixed>  $data  The event payload data
     */
    public function dispatch(string $event, array $data, string $userId): void
    {
        $endpoints = WebhookEndpoint::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($endpoints as $endpoint) {
            $this->dispatchToEndpoint($endpoint, $event, $data);
        }
    }

    /**
     * Dispatch a webhook to a specific endpoint.
     *
     * @param  array<string, mixed>  $data
     */
    public function dispatchToEndpoint(WebhookEndpoint $endpoint, string $event, array $data): WebhookDelivery
    {
        $payload = $this->buildPayload($event, $data);
        $signature = $this->signPayload($endpoint->secret, $payload);

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => $event,
            'payload' => $payload,
            'attempt_count' => 0,
            'created_at' => now(),
        ]);

        // Dispatch HTTP request
        $response = $this->sendRequest($endpoint->url, $payload, $signature);

        if ($response->successful()) {
            $delivery->markAsDelivered($response->status(), $response->body());
            $endpoint->last_triggered_at = now();
            $endpoint->save();
        } else {
            $delivery->markAsFailed($response->status(), $response->body());
            $delivery->next_retry_at = $delivery->calculateNextRetry();
            $delivery->save();

            // Schedule retry job (simplified - in production use queue)
            // WebhookDispatchJob::dispatch($delivery->id)->delay($delivery->next_retry_at);
        }

        return $delivery;
    }

    /**
     * Send a test payload to an endpoint.
     *
     * @return array<string, mixed>
     */
    public function dispatchTest(WebhookEndpoint $endpoint): array
    {
        return $this->sendTestPayload($endpoint);
    }

    /**
     * Send a test payload to an endpoint.
     *
     * @return array<string, mixed>
     */
    public function sendTestPayload(WebhookEndpoint $endpoint): array
    {
        $event = 'test.ping';
        $data = [
            'message' => 'This is a test webhook from Koomky',
            'timestamp' => now()->toIso8601String(),
        ];

        $delivery = $this->dispatchToEndpoint($endpoint, $event, $data);

        return [
            'success' => $delivery->delivered_at !== null,
            'status' => $delivery->response_status,
            'response_body' => $delivery->response_body,
        ];
    }

    /**
     * Retry a failed delivery.
     */
    public function retry(WebhookDelivery $delivery): WebhookDelivery
    {
        return $this->retryDelivery($delivery);
    }

    /**
     * Retry a failed delivery.
     */
    public function retryDelivery(WebhookDelivery $delivery): WebhookDelivery
    {
        if (! $delivery->canRetry()) {
            throw new \RuntimeException('Maximum retry attempts exceeded');
        }

        $endpoint = $delivery->endpoint;
        /** @var array<string, mixed> $payload */
        $payload = $delivery->payload;
        $payloadString = (string) json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->signPayload($endpoint->secret, $payloadString);

        $response = $this->sendRequest($endpoint->url, $payload, $signature);

        if ($response->successful()) {
            $delivery->markAsDelivered($response->status(), $response->body());
            $endpoint->last_triggered_at = now();
            $endpoint->save();
        } else {
            $delivery->markAsFailed($response->status(), $response->body());
            $delivery->next_retry_at = $delivery->calculateNextRetry();
            $delivery->save();
        }

        return $delivery;
    }

    /**
     * Build the webhook payload.
     *
     * @param  array<string, mixed>  $data
     */
    private function buildPayload(string $event, array $data): string
    {
        return json_encode([
            'event' => $event,
            'created_at' => now()->toIso8601String(),
            'data' => $data,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Sign the payload with HMAC-SHA256.
     */
    private function signPayload(string $secret, string $payload): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Send HTTP POST request to webhook endpoint.
     *
     * @param  array<string, mixed>|string  $payload
     */
    private function sendRequest(string $url, array|string $payload, string $signature): \Illuminate\Http\Client\Response
    {
        $payloadString = is_array($payload) ? json_encode($payload, JSON_THROW_ON_ERROR) : $payload;
        $payloadArray = is_array($payload) ? $payload : json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $eventName = is_array($payloadArray) && isset($payloadArray['event'])
            ? explode('.', (string) $payloadArray['event'])[0]
            : 'unknown';

        return Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Koomky-Signature' => $signature,
                'X-Koomky-Event' => $eventName,
                'X-Koomky-Delivery' => (string) Str::uuid(),
            ])
            ->post($url, $payloadArray);
    }
}
