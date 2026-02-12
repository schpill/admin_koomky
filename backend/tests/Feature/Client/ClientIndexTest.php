<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    User::unsetEventDispatcher();
});

it('returns authenticated users clients', function () {
    $user = User::factory()->create();

    Client::factory()->for($user)->count(3)->create();
    Client::factory()->for(User::factory()->create())->count(2)->create();

    actingAs($user)
        ->getJson('/api/v1/clients')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('paginates clients', function () {
    $user = User::factory()->create();

    Client::factory()->for($user)->count(25)->create();

    actingAs($user)
        ->getJson('/api/v1/clients?per_page=10')
        ->assertStatus(200)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 25);
});

it('filters clients by status', function () {
    $user = User::factory()->create();

    Client::factory()->for($user)->create(['status' => 'active']);
    Client::factory()->for($user)->create(['status' => 'archived']);

    actingAs($user)
        ->getJson('/api/v1/clients?status=active')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.status', 'active');
});

it('searches clients by name', function () {
    $user = User::factory()->create();

    Client::factory()->for($user)->create(['name' => 'Acme Corporation']);
    Client::factory()->for($user)->create(['name' => 'Globex Inc']);

    actingAs($user)
        ->getJson('/api/v1/clients?search=acme')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.name', 'Acme Corporation');
});

it('forbids accessing other users clients', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Client::factory()->for($otherUser)->create(['name' => 'Secret Client']);

    actingAs($user)
        ->getJson('/api/v1/clients')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');
});
