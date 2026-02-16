<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('login returns access and refresh tokens', function () {
    $user = User::factory()->create([
        'password' => bcrypt($password = 'Password123!'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['access_token', 'refresh_token', 'user', 'expires_in'],
        ]);

    $data = $response->json('data');

    // Verify tokens exist in database
    $this->assertCount(2, $user->tokens);

    $accessToken = PersonalAccessToken::findToken($data['access_token']);
    $refreshToken = PersonalAccessToken::findToken($data['refresh_token']);

    expect($accessToken->abilities)->toContain('access');
    expect($refreshToken->abilities)->toContain('refresh');
});

test('user can refresh their access token', function () {
    $user = User::factory()->create();
    $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(7))->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$refreshToken)
        ->postJson('/api/v1/auth/refresh');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['access_token', 'refresh_token', 'user'],
        ]);

    // Old refresh token should be deleted
    $this->assertNull(PersonalAccessToken::findToken($refreshToken));

    // New tokens should exist
    $this->assertCount(2, $user->fresh()->tokens);
});

test('user cannot refresh with an access token', function () {
    $user = User::factory()->create();
    $accessToken = $user->createToken('access_token', ['access'], now()->addMinutes(15))->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
        ->postJson('/api/v1/auth/refresh');

    $response->assertStatus(401);
});

test('expired refresh token cannot be used', function () {
    $user = User::factory()->create();
    $token = $user->createToken('refresh_token', ['refresh'], now()->subDay());
    $refreshToken = $token->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$refreshToken)
        ->postJson('/api/v1/auth/refresh');

    $response->assertStatus(401);
});
