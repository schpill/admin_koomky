<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\putJson;

beforeEach(function () {
    User::unsetEventDispatcher();
});

it('updates a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create([
        'first_name' => 'Old Name',
        'company_name' => null,
        'email' => 'old@example.com',
    ]);

    actingAs($user)
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.attributes.name', 'New Name')
        ->assertJsonPath('data.attributes.email', 'new@example.com');

    expect($client->fresh()->first_name)->toBe('New Name');
});

it('requires a name', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    actingAs($user)
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('forbids updating other users clients', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->for($otherUser)->create();

    actingAs($user)
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'Hacked Name',
        ])
        ->assertStatus(403);
});

it('archives a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['archived_at' => null]);

    actingAs($user)
        ->postJson("/api/v1/clients/{$client->id}/archive")
        ->assertStatus(200)
        ->assertJsonPath('data.attributes.status', 'archived');

    expect($client->fresh()->status)->toBe('archived');
});

it('restores an archived client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['archived_at' => now()]);

    actingAs($user)
        ->postJson("/api/v1/clients/{$client->id}/restore")
        ->assertStatus(200)
        ->assertJsonPath('data.attributes.status', 'active');

    expect($client->fresh()->status)->toBe('active');
});

it('deletes a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    actingAs($user)
        ->deleteJson("/api/v1/clients/{$client->id}")
        ->assertStatus(204);

    expect(Client::find($client->id))->toBeNull();
});

it('syncs tags on update', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    actingAs($user)
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => $client->first_name,
            'tags' => ['VIP', 'Enterprise'],
        ])
        ->assertStatus(200);

    expect($client->fresh()->tags)->toHaveCount(2);
});

it('replaces contacts on update', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    \App\Models\Contact::factory()->create(['client_id' => $client->id]);

    actingAs($user)
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => $client->first_name,
            'contacts' => [
                ['name' => 'New Contact', 'email' => 'new@example.com'],
            ],
        ])
        ->assertStatus(200);

    expect($client->fresh()->contacts)->toHaveCount(1);
    expect($client->fresh()->contacts->first()->email)->toBe('new@example.com');
});

it('logs activity on update', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    actingAs($user)
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'Updated Name',
        ])
        ->assertStatus(200);

    expect($client->activities()->count())->toBeGreaterThanOrEqual(1);
});

it('requires authentication to update', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    putJson("/api/v1/clients/{$client->id}", [
        'name' => 'Hacked',
    ])
        ->assertStatus(401);
});
