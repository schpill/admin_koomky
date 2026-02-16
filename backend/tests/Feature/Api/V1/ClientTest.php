<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can create a client', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/clients', [
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'phone' => '+33123456789',
            'address' => '123 Main St',
            'city' => 'Paris',
            'zip_code' => '75001',
            'country' => 'France',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'data' => [
                'id',
                'reference',
                'name',
                'email',
            ],
            'message',
        ]);

    $this->assertDatabaseHas('clients', [
        'name' => 'Acme Corp',
        'email' => 'contact@acme.com',
        'user_id' => $user->id,
    ]);

    // Check that reference follows pattern CLI-YYYY-NNNN
    $client = Client::first();
    expect($client->reference)->toMatch('/^CLI-\d{4}-\d{4}$/');
});

test('user can only see their own clients', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Client::factory()->create(['user_id' => $user->id, 'name' => 'My Client', 'reference' => 'CLI-2026-0001']);
    Client::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Client', 'reference' => 'CLI-2026-0002']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/clients');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.name', 'My Client');
});

test('user can view a specific client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'reference' => 'CLI-2026-0001']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/clients/{$client->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $client->id);
});

test('user can update a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'name' => 'Old Name', 'reference' => 'CLI-2026-0001']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'New Name',
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'New Name',
    ]);
});

test('user can delete a client (soft delete)', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'reference' => 'CLI-2026-0001']);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$client->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('clients', ['id' => $client->id]);
});

test('user can restore a soft deleted client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'reference' => 'CLI-2026-0001',
    ]);
    $client->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/restore");

    $response->assertStatus(200);
    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'deleted_at' => null,
    ]);
});

test('unauthenticated user cannot create a client', function () {
    $response = $this->postJson('/api/v1/clients', [
        'name' => 'Acme Corp',
    ]);

    $response->assertStatus(401);
});
