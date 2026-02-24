<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentEmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDocumentEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public Document $document)
    {
        $this->onQueue('embeddings');
    }

    public function handle(DocumentEmbeddingService $embeddingService): void
    {
        try {
            $embeddingService->indexDocument($this->document);
        } catch (\Throwable $exception) {
            $this->document->update(['embedding_status' => 'failed']);
            Log::error('document_embedding_failed', [
                'document_id' => $this->document->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
