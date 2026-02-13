<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
});

it('uploads a JPEG avatar', function () {
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    actingAs($this->user)
        ->postJson('/api/v1/settings/avatar', [
            'avatar' => $file,
        ])
        ->assertStatus(200);

    expect($this->user->fresh()->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($this->user->fresh()->avatar_path);
});

it('uploads a PNG avatar', function () {
    $file = UploadedFile::fake()->image('avatar.png', 200, 200);

    actingAs($this->user)
        ->postJson('/api/v1/settings/avatar', [
            'avatar' => $file,
        ])
        ->assertStatus(200);

    expect($this->user->fresh()->avatar_path)->not->toBeNull();
});

it('rejects non-image file', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    actingAs($this->user)
        ->postJson('/api/v1/settings/avatar', [
            'avatar' => $file,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['avatar']);
});

it('rejects file larger than 2MB', function () {
    $file = UploadedFile::fake()->image('large.jpg')->size(3000);

    actingAs($this->user)
        ->postJson('/api/v1/settings/avatar', [
            'avatar' => $file,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['avatar']);
});

it('requires avatar file', function () {
    actingAs($this->user)
        ->postJson('/api/v1/settings/avatar', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['avatar']);
});

it('replaces existing avatar', function () {
    // Upload first avatar
    $firstFile = UploadedFile::fake()->image('first.jpg', 200, 200);
    actingAs($this->user)
        ->postJson('/api/v1/settings/avatar', ['avatar' => $firstFile]);

    $oldPath = $this->user->fresh()->avatar_path;

    // Upload second avatar
    $secondFile = UploadedFile::fake()->image('second.jpg', 200, 200);
    actingAs($this->user)
        ->postJson('/api/v1/settings/avatar', ['avatar' => $secondFile])
        ->assertStatus(200);

    $newPath = $this->user->fresh()->avatar_path;

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

it('requires authentication for avatar upload', function () {
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $this->postJson('/api/v1/settings/avatar', [
        'avatar' => $file,
    ])
        ->assertStatus(401);
});
