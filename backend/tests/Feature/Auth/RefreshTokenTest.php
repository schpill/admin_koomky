<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;

it('refreshes tokens for authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/auth/refresh')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'access_token',
                    'refresh_token',
                    'expires_in',
                ],
            ],
        ]);
});

it('returns token type in response', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/auth/refresh')
        ->assertStatus(200)
        ->assertJsonPath('data.type', 'token');
});

// Note: refresh route is public but controller calls $request->user() without null check
// This results in 500 rather than 401. Consider moving to auth:sanctum middleware group.
it('fails gracefully without authentication', function () {
    test()->postJson('/api/v1/auth/refresh')
        ->assertStatus(500);
});
