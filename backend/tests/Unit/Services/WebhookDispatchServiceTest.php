<?php

use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = app(WebhookDispatchService::class);
});

test('dispatch sends webhook to all subscribed endpoints', function () {
    $endpoint1 = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
        'events' => ['invoice.created', 'invoice.updated'],
    ]);

    $endpoint2 = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
        'events' => ['invoice.created'],
    ]);

    // Endpoint that shouldn't receive this event
    WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
        'events' => ['client.created'],
    ]);

    Http::fake([
        $endpoint1->url => Http::response(['success' => true], 200),
        $endpoint2->url => Http::response(['success' => true], 200),
    ]);

    $this->service->dispatch('invoice.created', ['invoice_id' => 1], $this->user->id);

    Http::assertSentCount(2);
});

test('dispatch ignores inactive endpoints', function () {
    WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => false,
        'events' => ['invoice.created'],
    ]);

    Http::fake();

    $this->service->dispatch('invoice.created', ['invoice_id' => 1], $this->user->id);

    Http::assertNothingSent();
});

test('dispatch ignores other users endpoints', function () {
    $otherUser = User::factory()->create();

    WebhookEndpoint::factory()->create([
        'user_id' => $otherUser->id,
        'is_active' => true,
        'events' => ['invoice.created'],
    ]);

    Http::fake();

    $this->service->dispatch('invoice.created', ['invoice_id' => 1], $this->user->id);

    Http::assertNothingSent();
});

test('dispatchToEndpoint creates delivery record', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    Http::fake([
        $endpoint->url => Http::response(['success' => true], 200),
    ]);

    $delivery = $this->service->dispatchToEndpoint($endpoint, 'test.event', ['data' => 'value']);

    expect($delivery)->toBeInstanceOf(WebhookDelivery::class)
        ->and($delivery->event)->toBe('test.event')
        ->and($delivery->delivered_at)->not->toBeNull();
});

test('dispatchToEndpoint marks delivery as failed on error response', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    Http::fake([
        $endpoint->url => Http::response(['error' => 'fail'], 500),
    ]);

    $delivery = $this->service->dispatchToEndpoint($endpoint, 'test.event', ['data' => 'value']);

    expect($delivery->delivered_at)->toBeNull()
        ->and($delivery->response_status)->toBe(500)
        ->and($delivery->next_retry_at)->not->toBeNull();
});

test('dispatchToEndpoint updates endpoint last triggered at on success', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
        'last_triggered_at' => null,
    ]);

    Http::fake([
        $endpoint->url => Http::response(['success' => true], 200),
    ]);

    $this->service->dispatchToEndpoint($endpoint, 'test.event', ['data' => 'value']);

    $endpoint->refresh();
    expect($endpoint->last_triggered_at)->not->toBeNull();
});

test('sendTestPayload returns success result on successful delivery', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    Http::fake([
        $endpoint->url => Http::response(['success' => true], 200),
    ]);

    $result = $this->service->sendTestPayload($endpoint);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200);
});

test('sendTestPayload returns failure result on failed delivery', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    Http::fake([
        $endpoint->url => Http::response(['error' => 'fail'], 500),
    ]);

    $result = $this->service->sendTestPayload($endpoint);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(500);
});

test('retryDelivery throws exception when max retries exceeded', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'attempt_count' => 5,
    ]);

    expect(fn () => $this->service->retryDelivery($delivery))
        ->toThrow(RuntimeException::class, 'Maximum retry attempts exceeded');
});

test('retryDelivery succeeds on retry', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'payload' => ['event' => 'test.event', 'data' => ['id' => 1]],
        'attempt_count' => 1,
    ]);

    Http::fake([
        $endpoint->url => Http::response(['success' => true], 200),
    ]);

    $result = $this->service->retryDelivery($delivery);

    expect($result->delivered_at)->not->toBeNull();
});

test('retryDelivery increments attempt count on failure', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'payload' => ['event' => 'test.event', 'data' => ['id' => 1]],
        'attempt_count' => 1,
    ]);

    Http::fake([
        $endpoint->url => Http::response(['error' => 'fail'], 500),
    ]);

    $result = $this->service->retryDelivery($delivery);

    expect($result->delivered_at)->toBeNull()
        ->and($result->attempt_count)->toBe(2);
});

test('dispatch sends correct headers', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
        'secret' => 'test-secret-key',
        'events' => ['invoice.created'],
    ]);

    Http::fake([
        $endpoint->url => Http::response(['success' => true], 200),
    ]);

    $this->service->dispatch('invoice.created', ['invoice_id' => 123], $this->user->id);

    Http::assertSent(function ($request) use ($endpoint) {
        $signature = $request->header('X-Koomky-Signature')[0] ?? '';

        return $request->url() === $endpoint->url
            && $request->hasHeader('Content-Type', 'application/json')
            && str_starts_with($signature, 'sha256=')
            && $request->hasHeader('X-Koomky-Event', 'invoice')
            && $request->hasHeader('X-Koomky-Delivery');
    });
});
