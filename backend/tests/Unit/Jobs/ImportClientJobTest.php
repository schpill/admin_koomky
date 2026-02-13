<?php

declare(strict_types=1);

use App\Jobs\ImportClientJob;
use App\Models\Client;
use App\Models\User;

it('creates a client from imported data', function () {
    User::unsetEventDispatcher();
    Client::unsetEventDispatcher();

    $user = User::factory()->create();
    $data = [
        'company' => 'Acme Inc',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@acme.com',
        'phone' => '+33612345678',
        'vat_number' => 'FR12345678901',
        'website' => 'https://acme.com',
        'address' => '123 Main St',
        'city' => 'Paris',
        'postal_code' => '75001',
        'country' => 'France',
        'notes' => 'Imported client',
    ];

    $job = new ImportClientJob($user, $data);
    $job->handle(app(\App\Services\ReferenceGeneratorService::class));

    expect(Client::where('email', 'john@acme.com')->exists())->toBeTrue();

    $client = Client::where('email', 'john@acme.com')->first();
    expect($client->company_name)->toBe('Acme Inc');
    expect($client->first_name)->toBe('John');
    expect($client->user_id)->toBe($user->id);
});

it('creates activity log for imported client', function () {
    User::unsetEventDispatcher();
    Client::unsetEventDispatcher();

    $user = User::factory()->create();
    $data = ['first_name' => 'Jane', 'email' => 'jane@test.com'];

    $job = new ImportClientJob($user, $data);
    $job->handle(app(\App\Services\ReferenceGeneratorService::class));

    $client = Client::where('email', 'jane@test.com')->first();
    expect($client->activities()->count())->toBe(1);
    expect($client->activities()->first()->description)->toBe('Client imported via CSV');
});

it('is queued on imports queue', function () {
    $user = User::factory()->create();
    $job = new ImportClientJob($user, []);

    expect($job->queue)->toBe('imports');
});

it('handles missing data fields gracefully', function () {
    User::unsetEventDispatcher();
    Client::unsetEventDispatcher();

    $user = User::factory()->create();
    $data = ['first_name' => 'Minimal'];

    $job = new ImportClientJob($user, $data);
    $job->handle(app(\App\Services\ReferenceGeneratorService::class));

    $client = Client::where('first_name', 'Minimal')->first();
    expect($client)->not->toBeNull();
    expect($client->email)->toBeNull();
    expect($client->company_name)->toBeNull();
});
