<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can get their settings', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'business_name' => 'John Inc.',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/settings/profile');

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'John Doe')
        ->assertJsonPath('data.business_name', 'John Inc.');
});

test('user can update their profile', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);
});

test('user can update their business settings', function () {
    $user = User::factory()->create(['business_name' => 'Old Business']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/business', [
            'business_name' => 'New Business',
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'business_name' => 'New Business',
    ]);
});
