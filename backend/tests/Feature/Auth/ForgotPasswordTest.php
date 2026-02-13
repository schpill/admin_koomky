<?php

declare(strict_types=1);

use App\Models\User;

it('sends password reset link', function () {
    User::factory()->create(['email' => 'user@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'user@example.com',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.message', 'If the email exists, a password reset link has been sent.');
});

it('returns success even for non-existent email', function () {
    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.message', 'If the email exists, a password reset link has been sent.');
});

it('validates email is required for forgot password', function () {
    $this->postJson('/api/v1/auth/forgot-password', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates email format for forgot password', function () {
    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'not-an-email',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('resets password with valid data', function () {
    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'valid-reset-token',
        'email' => 'user@example.com',
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'NewSecurePass123!',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.message', 'Password has been reset successfully.');
});

it('validates token is required for reset password', function () {
    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'user@example.com',
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'NewSecurePass123!',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

it('validates email is required for reset password', function () {
    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'valid-reset-token',
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'NewSecurePass123!',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates password minimum length for reset password', function () {
    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'valid-reset-token',
        'email' => 'user@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('validates password confirmation for reset password', function () {
    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'valid-reset-token',
        'email' => 'user@example.com',
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'DifferentPass123!',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
