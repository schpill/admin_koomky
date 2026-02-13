<?php

declare(strict_types=1);

use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

it('transforms client into json api structure', function () {
    $user = User::factory()->create();
    User::unsetEventDispatcher();
    $client = Client::factory()->for($user)->create();

    $resource = (new ClientResource($client))->toArray(new Request);

    expect($resource)->toHaveKeys(['type', 'id', 'attributes', 'relationships']);
    expect($resource['type'])->toBe('client');
    expect($resource['id'])->toBe($client->id);
});

it('includes all client attributes', function () {
    $user = User::factory()->create();
    User::unsetEventDispatcher();
    $client = Client::factory()->for($user)->create();

    $resource = (new ClientResource($client))->toArray(new Request);
    $attributes = $resource['attributes'];

    expect($attributes)->toHaveKeys([
        'reference', 'name', 'email', 'phone', 'company',
        'vat_number', 'website', 'billing_address', 'notes',
        'status', 'created_at', 'updated_at',
    ]);
});

it('formats dates as ISO 8601', function () {
    $user = User::factory()->create();
    User::unsetEventDispatcher();
    $client = Client::factory()->for($user)->create();

    $resource = (new ClientResource($client))->toArray(new Request);

    expect($resource['attributes']['created_at'])->toContain('T');
});

it('includes relationships section', function () {
    $user = User::factory()->create();
    User::unsetEventDispatcher();
    $client = Client::factory()->for($user)->create();

    $resource = (new ClientResource($client))->toArray(new Request);

    expect($resource['relationships'])->toHaveKeys(['tags', 'contacts', 'activities']);
});
