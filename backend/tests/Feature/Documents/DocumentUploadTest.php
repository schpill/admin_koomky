<?php

use App\Models\User;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('authenticated user can upload a document', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/documents', [
        'file' => UploadedFile::fake()->create('contract.pdf', 100),
        'title' => 'My Contract',
        'client_id' => $client->id,
        'tags' => ['legal', 'important'],
    ]);

    if ($response->status() !== 201) {
        dump($response->json());
    }

    $response->assertStatus(201)
        ->assertJsonPath('title', 'My Contract')
        ->assertJsonPath('client_id', $client->id)
        ->assertJsonPath('document_type', 'pdf');

    $document = Document::first();
    Storage::disk('local')->assertExists($document->storage_path);
});

test('upload uses original filename if title is missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/documents', [
        'file' => UploadedFile::fake()->create('report_2024.docx', 100),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('title', 'report_2024');
});

test('upload rejects large files', function () {
    $user = User::factory()->create();
    config(['performance.max_document_upload_mb' => 1]); // 1MB limit

    $response = $this->actingAs($user)->postJson('/api/v1/documents', [
        'file' => UploadedFile::fake()->create('huge.zip', 2048), // 2MB
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('upload fails when quota is exceeded', function () {
    $user = User::factory()->create(['document_storage_quota_mb' => 0]); // 0MB quota

    $response = $this->actingAs($user)->postJson('/api/v1/documents', [
        'file' => UploadedFile::fake()->create('test.pdf', 100),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Storage quota exceeded');
});

test('upload rejects dangerous mimes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/documents', [
        'file' => UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload'),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Dangerous file type rejected: application/x-msdownload');
});
