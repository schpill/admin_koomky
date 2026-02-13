<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

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

    Client::factory()->for($user)->create(['archived_at' => null]);
    Client::factory()->for($user)->create(['archived_at' => now()]);

    actingAs($user)
        ->getJson('/api/v1/clients?status=active')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.status', 'active');
});

it('searches clients by name', function () {
    $user = User::factory()->create();

    Client::factory()->for($user)->create(['company_name' => 'Acme Corporation']);
    Client::factory()->for($user)->create(['company_name' => 'Globex Inc']);

    actingAs($user)
        ->getJson('/api/v1/clients?search=acme')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.name', 'Acme Corporation');
});

it('forbids accessing other users clients', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Client::factory()->for($otherUser)->create(['company_name' => 'Secret Client']);

    actingAs($user)
        ->getJson('/api/v1/clients')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

it('sorts clients by name ascending', function () {
    $user = User::factory()->create();

    Client::factory()->for($user)->create(['first_name' => 'Charlie', 'company_name' => null]);
    Client::factory()->for($user)->create(['first_name' => 'Alice', 'company_name' => null]);
    Client::factory()->for($user)->create(['first_name' => 'Bob', 'company_name' => null]);

    $response = actingAs($user)
        ->getJson('/api/v1/clients?sort_by=first_name&sort_order=asc')
        ->assertStatus(200);

    $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
    $sortedNames = collect($names)->sort()->values()->toArray();
    expect($names)->toBe($sortedNames);
});

it('filters clients by tag', function () {
    $user = User::factory()->create();

    $taggedClient = Client::factory()->for($user)->create();
    $untaggedClient = Client::factory()->for($user)->create();

    $tag = \App\Models\Tag::factory()->create(['user_id' => $user->id, 'name' => 'VIP']);
    $taggedClient->tags()->attach($tag);

    actingAs($user)
        ->getJson('/api/v1/clients?tag=VIP')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('caps per_page at 100', function () {
    $user = User::factory()->create();

    Client::factory()->for($user)->count(5)->create();

    actingAs($user)
        ->getJson('/api/v1/clients?per_page=500')
        ->assertStatus(200)
        ->assertJsonPath('meta.per_page', 100);
});

it('requires authentication', function () {
    getJson('/api/v1/clients')
        ->assertStatus(401);
});
