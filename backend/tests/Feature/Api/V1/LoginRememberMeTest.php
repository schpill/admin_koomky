<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('login issues a refresh token valid for 30 days when remember me is enabled', function () {
    Carbon::setTestNow('2026-03-08 10:00:00');

    $user = User::factory()->create([
        'password' => bcrypt($password = 'Password123!'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
        'remember_me' => true,
    ]);

    $response->assertOk();

    $refreshToken = PersonalAccessToken::findToken($response->json('data.refresh_token'));

    expect($refreshToken)->not->toBeNull()
        ->and($refreshToken->abilities)->toContain('refresh')
        ->and($refreshToken->expires_at?->equalTo(now()->addDays(30)))->toBeTrue();
});

test('login issues a refresh token valid for 24 hours when remember me is disabled', function () {
    Carbon::setTestNow('2026-03-08 10:00:00');

    $user = User::factory()->create([
        'password' => bcrypt($password = 'Password123!'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
        'remember_me' => false,
    ]);

    $response->assertOk();

    $refreshToken = PersonalAccessToken::findToken($response->json('data.refresh_token'));

    expect($refreshToken)->not->toBeNull()
        ->and($refreshToken->abilities)->toContain('refresh')
        ->and($refreshToken->expires_at?->equalTo(now()->addDay()))->toBeTrue();
});
