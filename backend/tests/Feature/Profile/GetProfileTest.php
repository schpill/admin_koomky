<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profile endpoint returns the authenticated user with avatar url', function () {
    $user = User::factory()->create([
        'name' => 'Profile Owner',
        'email' => 'profile@example.test',
        'avatar_path' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'Profile Owner')
        ->assertJsonPath('data.email', 'profile@example.test')
        ->assertJsonPath('data.avatar_url', null);
});
