<?php

use App\Models\User;
use App\Services\GeminiService;
use App\Services\RagService;
use App\Services\VectorSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('answer returns expected payload shape', function () {
    $user = User::factory()->create();

    $search = Mockery::mock(VectorSearchService::class);
    $search->shouldReceive('search')->andReturn(collect([
        (object) [
            'document_id' => 'doc-1',
            'title' => 'Titre',
            'chunk_index' => 0,
            'content' => 'Contexte A',
            'score' => 0.91,
        ],
    ]));

    $gemini = Mockery::mock(GeminiService::class);
    $gemini->shouldReceive('generate')->andReturn('Réponse RAG');

    $service = new RagService($search, $gemini);
    $result = $service->answer('Question ?', $user->id);

    expect($result)
        ->toHaveKeys(['answer', 'sources', 'tokens_used', 'latency_ms'])
        ->and($result['answer'])->toBe('Réponse RAG')
        ->and($result['sources'])->toHaveCount(1);
});

it('returns explicit fallback when no chunks found', function () {
    $user = User::factory()->create();

    $search = Mockery::mock(VectorSearchService::class);
    $search->shouldReceive('search')->andReturn(new Collection);

    $gemini = Mockery::mock(GeminiService::class);

    $service = new RagService($search, $gemini);
    $result = $service->answer('Question ?', $user->id);

    expect($result['answer'])->toContain('insuffisants');
});
