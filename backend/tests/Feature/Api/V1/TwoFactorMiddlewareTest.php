<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user without 2fa enabled can access protected routes normally', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard');

    $response->assertStatus(200);
});

test('user with 2fa enabled and full-access token can access protected routes', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);

    // Create token with access ability (not 2fa-pending)
    $token = $user->createToken('test', ['access']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/dashboard');

    $response->assertStatus(200);
});

test('user with 2fa enabled and pending token is blocked from protected routes', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);

    // Create token with 2fa-pending ability
    $token = $user->createToken('test', ['2fa-pending']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/dashboard');

    $response->assertStatus(403)
        ->assertJsonPath('code', '2FA_REQUIRED');
});

test('user with 2fa pending token can still access 2fa verify endpoint', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);

    $token = $user->createToken('test', ['2fa-pending']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/auth/2fa/verify', ['code' => '000000']);

    // Should not return 403 (2FA_REQUIRED), may return 422 for invalid code
    expect($response->status())->not->toBe(403);
});

test('user with 2fa pending token can still access logout endpoint', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);

    $token = $user->createToken('test', ['2fa-pending']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/auth/logout');

    // Should not return 403 (2FA_REQUIRED)
    expect($response->status())->not->toBe(403);
});

test('2fa middleware passes error response with correct structure', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);

    $token = $user->createToken('test', ['2fa-pending']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/clients');

    $response->assertStatus(403)
        ->assertJsonStructure(['status', 'message', 'code'])
        ->assertJsonPath('status', 'Error')
        ->assertJsonPath('message', 'Two-factor authentication required.');
});
