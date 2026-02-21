<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
});

test('it can download a document', function () {
    $document = Document::factory()->create([
        'user_id' => $this->user->id,
        'original_filename' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'storage_path' => 'documents/test.pdf',
    ]);
    Storage::disk('local')->put($document->storage_path, 'PDF content');

    $response = $this->actingAs($this->user)->get("/api/v1/documents/{$document->id}/download");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="test.pdf"');
});

test('it can preview a document inline', function () {
    $document = Document::factory()->create([
        'user_id' => $this->user->id,
        'original_filename' => 'test.png',
        'mime_type' => 'image/png',
        'storage_path' => 'documents/test.png',
    ]);
    Storage::disk('local')->put($document->storage_path, 'image data');

    $response = $this->actingAs($this->user)->get("/api/v1/documents/{$document->id}/download?inline=1");

    $response->assertStatus(200)
        ->assertHeader('Content-Disposition', 'inline; filename="test.png"');
});
