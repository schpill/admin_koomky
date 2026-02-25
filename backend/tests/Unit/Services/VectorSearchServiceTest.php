<?php

use App\Models\Document;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\VectorSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('search returns filtered chunks for user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $docA = Document::factory()->create(['user_id' => $user->id, 'client_id' => null]);
    $docB = Document::factory()->create(['user_id' => $other->id, 'client_id' => null]);

    DB::table('document_chunks')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'document_id' => $docA->id,
        'user_id' => $user->id,
        'chunk_index' => 0,
        'content' => 'Chunk A',
        'embedding' => json_encode([0.1, 0.2, 0.3], JSON_THROW_ON_ERROR),
        'token_count' => 10,
        'created_at' => now(),
    ]);

    DB::table('document_chunks')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'document_id' => $docB->id,
        'user_id' => $other->id,
        'chunk_index' => 0,
        'content' => 'Chunk B',
        'embedding' => json_encode([0.9, 0.8, 0.7], JSON_THROW_ON_ERROR),
        'token_count' => 10,
        'created_at' => now(),
    ]);

    $gemini = Mockery::mock(GeminiService::class);
    $gemini->shouldReceive('embed')->andReturn([0.1, 0.2, 0.3]);

    $service = new VectorSearchService($gemini);

    $results = $service->search('question', $user->id, 5);

    expect($results)->toHaveCount(1)
        ->and($results->first()->document_id)->toBe($docA->id);
});
