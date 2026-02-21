<?php

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it returns correct document stats', function () {
    $user = User::factory()->create(['document_storage_quota_mb' => 10]);

    Document::factory()->create([
        'user_id' => $user->id,
        'document_type' => DocumentType::PDF,
        'file_size' => 1024 * 512, // 0.5 MB
    ]);

    Document::factory()->create([
        'user_id' => $user->id,
        'document_type' => DocumentType::IMAGE,
        'file_size' => 1024 * 256, // 0.25 MB
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/documents/stats');

    $response->assertStatus(200)
        ->assertJsonPath('total_count', 2)
        ->assertJsonPath('total_size_bytes', 786432)
        ->assertJsonPath('quota_bytes', 10485760);

    $response->assertJsonStructure([
        'total_count',
        'total_size_bytes',
        'quota_bytes',
        'usage_percentage',
        'by_type' => [
            '*' => ['document_type', 'count', 'size'],
        ],
    ]);
});
