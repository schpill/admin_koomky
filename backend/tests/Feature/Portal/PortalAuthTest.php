<?php

use App\Mail\PortalInvitationMail;
use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('client can request a portal magic link', function () {
    Mail::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'portal@example.test',
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $response = $this->postJson('/api/v1/portal/auth/request', [
        'email' => $client->email,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('portal_access_tokens', [
        'client_id' => $client->id,
        'email' => $client->email,
        'is_active' => true,
    ]);

    Mail::assertSent(PortalInvitationMail::class);
});

test('valid magic token can be verified', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'verify@example.test',
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $portalAccessToken = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
        'email' => $client->email,
        'token' => str_repeat('a', 64),
        'is_active' => true,
        'expires_at' => now()->addHour(),
    ]);

    $response = $this->getJson('/api/v1/portal/auth/verify/'.$portalAccessToken->token);

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.client.id', $client->id)
        ->assertJsonPath('data.portal_token', fn ($value): bool => is_string($value) && $value !== '');

    $portalAccessToken->refresh();

    expect($portalAccessToken->last_used_at)->not->toBeNull();
});

test('expired magic token is rejected', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $portalAccessToken = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
        'token' => str_repeat('b', 64),
        'is_active' => true,
        'expires_at' => now()->subMinute(),
    ]);

    $this->getJson('/api/v1/portal/auth/verify/'.$portalAccessToken->token)
        ->assertStatus(401);
});

test('inactive magic token is rejected', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $portalAccessToken = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
        'token' => str_repeat('c', 64),
        'is_active' => false,
        'expires_at' => now()->addHour(),
    ]);

    $this->getJson('/api/v1/portal/auth/verify/'.$portalAccessToken->token)
        ->assertStatus(401);
});

test('portal logout invalidates the session token', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'logout@example.test',
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $portalAccessToken = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
        'email' => $client->email,
        'token' => str_repeat('d', 64),
        'is_active' => true,
        'expires_at' => now()->addHour(),
    ]);

    $verifyResponse = $this->getJson('/api/v1/portal/auth/verify/'.$portalAccessToken->token);
    $verifyResponse->assertStatus(200);

    $portalToken = (string) $verifyResponse->json('data.portal_token');
    $headers = [
        'Authorization' => 'Bearer '.$portalToken,
    ];

    $this->getJson('/api/v1/portal/dashboard', $headers)
        ->assertStatus(200);

    $this->postJson('/api/v1/portal/auth/logout', [], $headers)
        ->assertStatus(200);

    $this->getJson('/api/v1/portal/dashboard', $headers)
        ->assertStatus(401);
});
