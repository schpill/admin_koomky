<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');

    // Mock WebhookDispatchService
    $this->webhookService = \Mockery::mock(\App\Services\WebhookDispatchService::class);
    $this->webhookService->shouldReceive('dispatch')->byDefault();
    $this->app->instance(\App\Services\WebhookDispatchService::class, $this->webhookService);
});

test('convert lead to client creates new client', function () {
    $lead = \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'company_name' => 'Acme Corp',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@acme.com',
        'phone' => '+1234567890',
    ]);

    $service = $this->app->make(\App\Services\LeadConversionService::class);
    $client = $service->convert($lead);

    expect($client->user_id)->toBe($this->user->id)
        ->and($client->email)->toBe('john@acme.com')
        ->and($client->phone)->toBe('+1234567890')
        ->and($lead->fresh()->won_client_id)->toBe($client->id)
        ->and($lead->fresh()->converted_at)->not->toBeNull();
});

test('convert lead rejects non won status', function () {
    $lead = \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);

    $service = $this->app->make(\App\Services\LeadConversionService::class);

    expect(fn () => $service->convert($lead))->toThrow(\RuntimeException::class);
});

test('convert lead rejects already converted lead', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $lead = \App\Models\Lead::factory()->converted()->create([
        'user_id' => $this->user->id,
        'won_client_id' => $client->id,
    ]);

    $service = $this->app->make(\App\Services\LeadConversionService::class);

    expect(fn () => $service->convert($lead))->toThrow(\RuntimeException::class);
});

test('convert lead links to existing client with same email', function () {
    $existingClient = \App\Models\Client::factory()->create([
        'user_id' => $this->user->id,
        'email' => 'john@acme.com',
    ]);

    $lead = \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'email' => 'john@acme.com',
    ]);

    $service = $this->app->make(\App\Services\LeadConversionService::class);
    $client = $service->convert($lead);

    expect($client->id)->toBe($existingClient->id)
        ->and($lead->fresh()->won_client_id)->toBe($existingClient->id);
});

test('convert lead allows field overrides', function () {
    $lead = \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'company_name' => 'Acme Corp',
        'email' => 'john@acme.com',
    ]);

    $service = $this->app->make(\App\Services\LeadConversionService::class);
    $client = $service->convert($lead, [
        'name' => 'Custom Name',
    ]);

    expect($client->name)->toBe('Custom Name');
});

test('convert lead logs activity', function () {
    $lead = \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);

    $service = $this->app->make(\App\Services\LeadConversionService::class);
    $service->convert($lead);

    expect($lead->activities)->toHaveCount(1)
        ->and($lead->activities->first()->type)->toBe('note');
});
