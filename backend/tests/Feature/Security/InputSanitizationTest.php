<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('client payload strips script and html tags before persistence', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/clients', [
            'name' => '<script>alert("x")</script>Acme <b>Corp</b>',
            'email' => 'security-client@example.test',
            'notes' => '<img src=x onerror=alert(1)>Trusted <i>partner</i>',
            'address' => '<p>221B Baker Street</p>',
            'city' => '<strong>London</strong>',
            'country' => '<span>UK</span>',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Acme Corp')
        ->assertJsonPath('data.notes', 'Trusted partner')
        ->assertJsonPath('data.address', '221B Baker Street')
        ->assertJsonPath('data.city', 'London')
        ->assertJsonPath('data.country', 'UK');

    $clientId = (string) $response->json('data.id');

    $client = Client::query()->findOrFail($clientId);

    expect($client->name)->toBe('Acme Corp');
    expect($client->notes)->toBe('Trusted partner');
    expect($client->address)->toBe('221B Baker Street');
});

test('client update sanitizes html payloads', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/clients/'.$client->id, [
            'name' => '<svg onload=alert(1)>Mega Corp</svg>',
            'notes' => '<script>alert("x")</script>safe text',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Mega Corp')
        ->assertJsonPath('data.notes', 'safe text');
});
