<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
});

test('it can reupload a document', function () {
    $document = Document::factory()->create([
        'user_id' => $this->user->id,
        'original_filename' => 'old.pdf',
        'version' => 1,
        'storage_path' => 'documents/old.pdf',
    ]);
    Storage::disk('local')->put($document->storage_path, 'old content');

    $response = $this->actingAs($this->user)->postJson("/api/v1/documents/{$document->id}/reupload", [
        'file' => UploadedFile::fake()->create('new_version.pdf', 200),
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('version', 2)
        ->assertJsonPath('original_filename', 'new_version.pdf');

    $document->refresh();
    expect($document->version)->toBe(2)
        ->and(Storage::disk('local')->get($document->storage_path))->not->toBe('old content');
});
