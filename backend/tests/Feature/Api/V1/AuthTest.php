<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register with valid data', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'business_name' => 'John Inc.',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'data' => [
                'user' => ['id', 'name', 'email', 'business_name'],
                'access_token',
            ],
            'message'
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'business_name' => 'John Inc.',
    ]);
});

test('user cannot register with invalid password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response->assertStatus(422);
});

test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt($password = 'Password123!'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['access_token', 'user']
        ]);
});

test('user cannot login with wrong credentials', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.email', $user->email);
});

test('unauthenticated user cannot get their profile', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});
