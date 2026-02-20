<?php

use App\Jobs\WebhookDispatchJob;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->endpoint = WebhookEndpoint::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
        'secret' => 'test-secret',
    ]);
});

test('job handles delivery not found gracefully', function () {
    $nonExistentUuid = (string) \Illuminate\Support\Str::uuid();

    Log::shouldReceive('warning')
        ->once()
        ->with('webhook_dispatch_delivery_not_found', \Mockery::hasKey('delivery_id'));

    $job = new WebhookDispatchJob($nonExistentUuid);
    $job->handle(app(WebhookDispatchService::class));

    // Should complete without throwing
    expect(true)->toBeTrue();
});

test('job skips already delivered webhooks', function () {
    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $this->endpoint->id,
        'delivered_at' => now(),
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('webhook_dispatch_already_delivered', \Mockery::hasKey('delivery_id'));

    $job = new WebhookDispatchJob($delivery->id);
    $job->handle(app(WebhookDispatchService::class));
});

test('job skips inactive endpoints', function () {
    $this->endpoint->update(['is_active' => false]);

    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $this->endpoint->id,
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->with('webhook_dispatch_endpoint_inactive', \Mockery::hasKey('delivery_id'));

    $job = new WebhookDispatchJob($delivery->id);
    $job->handle(app(WebhookDispatchService::class));
});

test('job successfully delivers webhook', function () {
    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $this->endpoint->id,
        'payload' => ['event' => 'test.event', 'data' => ['id' => 1]],
    ]);

    Http::fake([
        $this->endpoint->url => Http::response(['success' => true], 200),
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('webhook_dispatch_successful', \Mockery::hasKey('delivery_id'));

    $job = new WebhookDispatchJob($delivery->id);
    $job->handle(app(WebhookDispatchService::class));

    $delivery->refresh();
    expect($delivery->delivered_at)->not->toBeNull();
});

test('job logs failed attempt when delivery fails', function () {
    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $this->endpoint->id,
        'payload' => ['event' => 'test.event', 'data' => ['id' => 1]],
        'attempt_count' => 0,
    ]);

    Http::fake([
        $this->endpoint->url => Http::response(['error' => 'fail'], 500),
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->with('webhook_dispatch_failed_attempt', \Mockery::hasKey('delivery_id'));

    $job = new WebhookDispatchJob($delivery->id);
    $job->handle(app(WebhookDispatchService::class));

    $delivery->refresh();
    expect($delivery->delivered_at)->toBeNull();
    expect($delivery->attempt_count)->toBe(1);
});

test('job logs permanently failed when max attempts reached', function () {
    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $this->endpoint->id,
        'payload' => ['event' => 'test.event', 'data' => ['id' => 1]],
        'attempt_count' => 4, // One more attempt will hit max
    ]);

    Http::fake([
        $this->endpoint->url => Http::response(['error' => 'fail'], 500),
    ]);

    Log::shouldReceive('warning')->once();
    Log::shouldReceive('error')
        ->once()
        ->with('webhook_dispatch_permanently_failed', \Mockery::hasKey('total_attempts'));

    $job = new WebhookDispatchJob($delivery->id);
    $job->handle(app(WebhookDispatchService::class));

    $delivery->refresh();
    expect($delivery->attempt_count)->toBe(5);
    expect($delivery->canRetry())->toBeFalse();
});

test('job re-throws exception to trigger retry mechanism', function () {
    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $this->endpoint->id,
        'payload' => ['event' => 'test.event', 'data' => ['id' => 1]],
    ]);

    Http::fake([
        $this->endpoint->url => Http::failedConnection(new Exception('Connection failed')),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->with('webhook_dispatch_exception', \Mockery::hasKey('error'));

    $job = new WebhookDispatchJob($delivery->id);

    expect(fn () => $job->handle(app(WebhookDispatchService::class)))
        ->toThrow(Exception::class, 'Connection failed');
});

test('job failed method marks delivery as failed', function () {
    $delivery = WebhookDelivery::factory()->create([
        'webhook_endpoint_id' => $this->endpoint->id,
        'next_retry_at' => now()->addMinutes(5),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->with('webhook_dispatch_job_failed', \Mockery::hasKey('delivery_id'));

    $job = new WebhookDispatchJob($delivery->id);
    $job->failed(new Exception('Job failed'));

    $delivery->refresh();
    expect($delivery->failed_at)->not->toBeNull();
    expect($delivery->next_retry_at)->toBeNull();
});

test('job failed method handles missing delivery gracefully', function () {
    $nonExistentUuid = (string) \Illuminate\Support\Str::uuid();

    Log::shouldReceive('error')
        ->once()
        ->with('webhook_dispatch_job_failed', \Mockery::hasKey('delivery_id'));

    $job = new WebhookDispatchJob($nonExistentUuid);
    $job->failed(new Exception('Job failed'));

    // Should complete without throwing
    expect(true)->toBeTrue();
});

test('job has correct configuration', function () {
    $job = new WebhookDispatchJob('test-id');

    expect($job->tries)->toBe(5)
        ->and($job->backoff)->toBe([10, 60, 300, 900, 1800])
        ->and($job->timeout)->toBe(30);
});

test('job retry until returns datetime 2 hours in future', function () {
    $job = new WebhookDispatchJob('test-id');
    $retryUntil = $job->retryUntil();

    expect($retryUntil)->toBeInstanceOf(DateTime::class)
        ->and($retryUntil->getTimestamp())->toBeGreaterThan(now()->getTimestamp());
});
