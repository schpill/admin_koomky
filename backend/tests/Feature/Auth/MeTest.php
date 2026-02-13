<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;

it('returns current authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(200);
});

it('returns user data in response', function () {
    $user = User::factory()->create(['name' => 'Jean Dupont']);

    $response = actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(200);

    expect($response->json())->toHaveKey('data');
});

it('requires authentication', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertStatus(401);
});

it('returns correct JSON structure', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes',
            ],
        ])
        ->assertJsonPath('data.type', 'user');
});

it('does not expose hidden fields', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(200);

    $attributes = $response->json('data.attributes');
    expect($attributes)->not->toHaveKey('password');
    expect($attributes)->not->toHaveKey('two_factor_secret');
    expect($attributes)->not->toHaveKey('two_factor_recovery_codes');
    expect($attributes)->not->toHaveKey('bank_details');
    expect($attributes)->not->toHaveKey('remember_token');
});

it('returns user email and name in attributes', function () {
    $user = User::factory()->create([
        'name' => 'Marie Curie',
        'email' => 'marie@example.com',
    ]);

    actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('data.attributes.name', 'Marie Curie')
        ->assertJsonPath('data.attributes.email', 'marie@example.com');
});
