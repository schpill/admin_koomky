<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('user can request a password reset link', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'john@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('forgot password does not reveal whether an email exists', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'unknown@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success')
        ->assertJsonPath('message', 'If your email address exists in our system, a reset link has been sent.');

    Notification::assertNothingSent();
});

test('user can reset password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('OldPassword123!'),
    ]);

    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'john@example.com',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success');

    expect(Hash::check('NewPassword123!', $user->refresh()->password))->toBeTrue();
});

test('user cannot reset password with invalid token', function () {
    $user = User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => 'john@example.com',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertStatus(422);
});
