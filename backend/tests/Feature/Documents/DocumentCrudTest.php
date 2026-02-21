<?php

use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

test('it lists only documents belonging to the user', function () {
    Document::factory()->count(3)->create(['user_id' => $this->user->id]);
    Document::factory()->count(2)->create(['user_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/documents');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('it filters by document type', function () {
    Document::factory()->create(['user_id' => $this->user->id, 'document_type' => DocumentType::PDF]);
    Document::factory()->create(['user_id' => $this->user->id, 'document_type' => DocumentType::IMAGE]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/documents?document_type=pdf');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.document_type', 'pdf');
});

test('it filters by client_id', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    Document::factory()->create(['user_id' => $this->user->id, 'client_id' => $client->id]);
    Document::factory()->create(['user_id' => $this->user->id, 'client_id' => null]);

    $response = $this->actingAs($this->user)->getJson("/api/v1/documents?client_id={$client->id}");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.client_id', $client->id);
});

test('it returns document details', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->getJson("/api/v1/documents/{$document->id}");

    $response->assertStatus(200)
        ->assertJsonPath('id', $document->id);
});

test('it prevents viewing other users document', function () {
    $document = Document::factory()->create(['user_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)->getJson("/api/v1/documents/{$document->id}");

    $response->assertStatus(403);
});

test('it updates document metadata', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id, 'title' => 'Old Title']);
    $newClient = Client::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->putJson("/api/v1/documents/{$document->id}", [
        'title' => 'New Title',
        'client_id' => $newClient->id,
        'tags' => ['updated'],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('title', 'New Title')
        ->assertJsonPath('client_id', $newClient->id);
});

test('it deletes a document and its file', function () {
    $document = Document::factory()->create([
        'user_id' => $this->user->id,
        'storage_path' => 'documents/test.pdf',
    ]);
    Storage::disk('local')->put($document->storage_path, 'content');

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/documents/{$document->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    Storage::disk('local')->assertMissing($document->storage_path);
});
