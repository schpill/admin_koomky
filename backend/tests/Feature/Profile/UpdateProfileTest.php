<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('profile update changes name and email', function () {
    $user = User::factory()->create([
        'name' => 'Before Name',
        'email' => 'before@example.test',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/profile', [
            'name' => 'After Name',
            'email' => 'after@example.test',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'After Name')
        ->assertJsonPath('data.email', 'after@example.test');

    expect($user->fresh())
        ->name->toBe('After Name')
        ->email->toBe('after@example.test');
});

test('profile update rejects duplicate email addresses', function () {
    $user = User::factory()->create([
        'email' => 'owner@example.test',
    ]);

    User::factory()->create([
        'email' => 'taken@example.test',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/profile', [
            'name' => $user->name,
            'email' => 'taken@example.test',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('profile update stores uploaded avatar and returns avatar url', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'avatar_path' => null,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->patch('/api/v1/profile', [
            'name' => 'Avatar Owner',
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('avatar.png', 128, 'image/png'),
        ], [
            'Accept' => 'application/json',
        ]);

    $response->assertOk();

    $fresh = $user->fresh();

    expect($fresh)->not->toBeNull();
    expect($fresh->avatar_path)->toStartWith('avatars/'.$user->id.'.');
    Storage::disk('public')->assertExists((string) $fresh->avatar_path);

    $response->assertJsonPath('data.avatar_url', Storage::disk('public')->url((string) $fresh->avatar_path));
});
