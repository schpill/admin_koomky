<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('can list webhook endpoints', function () {
    \App\Models\WebhookEndpoint::factory()->count(2)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/v1/settings/webhooks');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data.data');
});

test('can create webhook endpoint', function () {
    $response = $this->postJson('/api/v1/settings/webhooks', [
        'name' => 'My Webhook',
        'url' => 'https://example.com/webhook',
        'events' => ['invoice.created', 'invoice.paid'],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'My Webhook')
        ->assertJsonStructure(['data' => ['secret']]); // Secret shown once
});

test('create webhook requires https url', function () {
    $response = $this->postJson('/api/v1/settings/webhooks', [
        'name' => 'Test',
        'url' => 'http://example.com/webhook',
        'events' => ['invoice.created'],
    ]);

    $response->assertStatus(422);
});

test('can update webhook endpoint', function () {
    $endpoint = \App\Models\WebhookEndpoint::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson("/api/v1/settings/webhooks/{$endpoint->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Name');
});

test('can delete webhook endpoint', function () {
    $endpoint = \App\Models\WebhookEndpoint::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/v1/settings/webhooks/{$endpoint->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('webhook_endpoints', ['id' => $endpoint->id]);
});

test('user cannot access other users webhooks', function () {
    $otherUser = \App\Models\User::factory()->create();
    $endpoint = \App\Models\WebhookEndpoint::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/v1/settings/webhooks/{$endpoint->id}");

    $response->assertStatus(404);
});
