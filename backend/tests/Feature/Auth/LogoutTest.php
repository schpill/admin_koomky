<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;

it('logs out authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/auth/logout')
        ->assertStatus(200);
});

it('invalidates tokens on logout', function () {
    $user = User::factory()->create();
    $user->createToken('test-token');

    expect($user->tokens()->count())->toBeGreaterThan(0);

    actingAs($user)
        ->postJson('/api/v1/auth/logout')
        ->assertStatus(200);

    expect($user->tokens()->count())->toBe(0);
});

it('requires authentication for logout', function () {
    $this->postJson('/api/v1/auth/logout')
        ->assertStatus(401);
});
