<?php

use App\Models\User;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
});

test('it can bulk delete documents', function () {
    $documents = Document::factory()->count(3)->create(['user_id' => $this->user->id]);
    $ids = $documents->pluck('id')->toArray();

    foreach ($documents as $doc) {
        Storage::disk('local')->put($doc->storage_path, 'content');
    }

    $response = $this->actingAs($this->user)->deleteJson('/api/v1/documents/bulk', [
        'ids' => $ids,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', '3 documents deleted');

    foreach ($documents as $doc) {
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        Storage::disk('local')->assertMissing($doc->storage_path);
    }
});

test('it does not delete documents belonging to other users', function () {
    $otherUser = User::factory()->create();
    $myDoc = Document::factory()->create(['user_id' => $this->user->id]);
    $otherDoc = Document::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)->deleteJson('/api/v1/documents/bulk', [
        'ids' => [$myDoc->id, $otherDoc->id],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', '1 documents deleted');

    $this->assertDatabaseMissing('documents', ['id' => $myDoc->id]);
    $this->assertDatabaseHas('documents', ['id' => $otherDoc->id]);
});
