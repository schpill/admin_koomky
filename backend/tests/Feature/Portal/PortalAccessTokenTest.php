<?php

use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\PortalActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create portal access token for a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'client-portal@example.test',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/clients/'.$client->id.'/portal-access', [
            'email' => $client->email,
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ]);

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.email', $client->email);

    $this->assertDatabaseHas('portal_access_tokens', [
        'client_id' => $client->id,
        'email' => $client->email,
        'is_active' => true,
    ]);
});

test('admin can revoke a portal token', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    $portalAccessToken = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/clients/'.$client->id.'/portal-access/'.$portalAccessToken->id)
        ->assertStatus(200);

    $portalAccessToken->refresh();
    expect($portalAccessToken->is_active)->toBeFalse();
});

test('admin can view portal activity logs for a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    $portalAccessToken = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
    ]);

    PortalActivityLog::factory()->count(2)->create([
        'client_id' => $client->id,
        'portal_access_token_id' => $portalAccessToken->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/clients/'.$client->id.'/portal-activity');

    $response->assertStatus(200);

    expect($response->json('data.data'))->toBeArray();
    expect($response->json('data.data'))->toHaveCount(2);
});
