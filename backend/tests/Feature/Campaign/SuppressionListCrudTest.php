<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('user can list search create delete import and export suppression entries', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/suppression-list', [
            'email' => 'manual@example.test',
            'reason' => 'manual',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'manual@example.test');

    $csv = UploadedFile::fake()->createWithContent(
        'suppression.csv',
        "email\nalpha@example.test\nbeta@example.test\n"
    );

    $this->actingAs($user, 'sanctum')
        ->post('/api/v1/suppression-list/import', [
            'file' => $csv,
        ])
        ->assertOk()
        ->assertJsonPath('data.imported', 2);

    $indexResponse = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/suppression-list?search=manual@example.test');

    $indexResponse->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.email', 'manual@example.test');

    $entryId = $indexResponse->json('data.data.0.id');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/suppression-list/'.$entryId)
        ->assertOk();

    $exportResponse = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/suppression-list/export');

    $exportResponse->assertOk();
    expect($exportResponse->getContent())->toContain('alpha@example.test');
});

test('user cannot delete another users suppression entry', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $entry = \App\Models\SuppressedEmail::query()->create([
        'user_id' => $owner->id,
        'email' => 'blocked@example.test',
        'reason' => 'manual',
        'suppressed_at' => now(),
    ]);

    $this->actingAs($intruder, 'sanctum')
        ->deleteJson('/api/v1/suppression-list/'.$entry->id)
        ->assertForbidden();
});
