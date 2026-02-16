<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can update business name', function () {
    $user = User::factory()->create(['business_name' => 'Old Biz']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/business', [
            'business_name' => 'New Biz Corp',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success')
        ->assertJsonPath('message', 'Business settings updated successfully');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'business_name' => 'New Biz Corp',
    ]);
});

test('business name is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/business', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('business_name');
});

test('business name cannot exceed 255 characters', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/business', [
            'business_name' => str_repeat('a', 256),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('business_name');
});

test('user profile returns correct data', function () {
    $user = User::factory()->create([
        'name' => 'Alice Wonder',
        'email' => 'alice@example.com',
        'business_name' => 'Alice Corp',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/settings/profile');

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Alice Wonder')
        ->assertJsonPath('data.email', 'alice@example.com')
        ->assertJsonPath('data.business_name', 'Alice Corp');
});

test('user can update profile name and email', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Profile updated successfully');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);
});

test('unauthenticated user cannot access profile settings', function () {
    $response = $this->getJson('/api/v1/settings/profile');

    $response->assertStatus(401);
});

test('unauthenticated user cannot update business settings', function () {
    $response = $this->putJson('/api/v1/settings/business', [
        'business_name' => 'Hack Corp',
    ]);

    $response->assertStatus(401);
});

test('user can disable 2fa', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);

    // Use real token with access ability (not 2fa-pending) to pass middleware
    $token = $user->createToken('test', ['access']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/settings/2fa/disable');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Two-factor authentication disabled.');

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
});

test('confirm 2fa with invalid code returns error', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'TESTSECRETBASE32',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/settings/2fa/confirm', [
            'code' => '000000',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid verification code');
});

test('confirm 2fa requires 6-digit code', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/settings/2fa/confirm', [
            'code' => '123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('code');
});
