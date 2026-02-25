<?php

use App\Jobs\ProcessDocumentEmbeddingJob;
use App\Models\Document;
use App\Services\DocumentEmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('has expected queue configuration', function () {
    $document = Document::factory()->create();
    $job = new ProcessDocumentEmbeddingJob($document);

    expect($job->queue)->toBe('embeddings')
        ->and($job->timeout)->toBe(120)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([30, 120, 300]);
});

it('marks document as failed when indexing throws', function () {
    $document = Document::factory()->create(['embedding_status' => 'pending']);

    $service = Mockery::mock(DocumentEmbeddingService::class);
    $service->shouldReceive('indexDocument')->andThrow(new RuntimeException('boom'));

    $job = new ProcessDocumentEmbeddingJob($document);
    $job->handle($service);

    expect($document->fresh()->embedding_status)->toBe('failed');
});
