<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    User::unsetEventDispatcher();
});

it('updates a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create([
        'name' => 'Old Name',
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

    expect($client->fresh()->name)->toBe('New Name');
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
    $client = Client::factory()->for($user)->create(['status' => 'active']);

    actingAs($user)
        ->postJson("/api/v1/clients/{$client->id}/archive")
        ->assertStatus(200)
        ->assertJsonPath('data.attributes.status', 'archived');

    expect($client->fresh()->status)->toBe('archived');
});

it('restores an archived client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['status' => 'archived']);

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
