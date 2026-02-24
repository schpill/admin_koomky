<?php

use App\Models\Document;
use App\Services\DocumentChunkService;
use App\Services\DocumentEmbeddingService;
use App\Services\DocumentTextExtractorService;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('indexDocument stores chunks and updates status', function () {
    $document = Mockery::mock(Document::class)->makePartial();
    $document->id = 'doc-id';
    $document->user_id = 'user-id';
    $document->mime_type = 'text/plain';
    $document->embedding_status = 'pending';
    $document->shouldReceive('update')->with(['embedding_status' => 'indexing'])->once();
    $document->shouldReceive('update')->with(['embedding_status' => 'indexed'])->once();

    DB::shouldReceive('table')->with('document_chunks')->andReturnSelf()->atLeast()->once();
    DB::shouldReceive('where')->with('document_id', Mockery::any())->andReturnSelf()->once();
    DB::shouldReceive('delete')->once();
    DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
        return $callback();
    });
    DB::shouldReceive('insert')->atLeast()->once()->with(Mockery::on(function (array $payload): bool {
        return array_key_exists('document_id', $payload)
            && array_key_exists('user_id', $payload)
            && is_string($payload['embedding'])
            && array_key_exists('content', $payload)
            && array_key_exists('token_count', $payload);
    }));

    $extractor = Mockery::mock(DocumentTextExtractorService::class);
    $extractor->shouldReceive('extract')->andReturn('texte test '.str_repeat('mot ', 200));

    $chunker = new DocumentChunkService;

    $gemini = Mockery::mock(GeminiService::class);
    $gemini->shouldReceive('embed')->atLeast()->once()->andReturn([0.1, 0.2, 0.3]);

    $service = new DocumentEmbeddingService($extractor, $chunker, $gemini);
    $service->indexDocument($document);
});

it('deleteDocumentChunks removes all chunks and clears status', function () {
    $document = Mockery::mock(Document::class)->makePartial();
    $document->id = 'doc-id';
    $document->shouldReceive('update')->with(['embedding_status' => null])->once();

    DB::shouldReceive('table')->with('document_chunks')->andReturnSelf()->once();
    DB::shouldReceive('where')->with('document_id', Mockery::any())->andReturnSelf()->once();
    DB::shouldReceive('delete')->once();

    $service = app(DocumentEmbeddingService::class);
    $service->deleteDocumentChunks($document);
});
