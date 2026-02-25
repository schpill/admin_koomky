<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

class DocumentEmbeddingService
{
    public function __construct(
        protected DocumentTextExtractorService $extractor,
        protected DocumentChunkService $chunker,
        protected GeminiService $gemini
    ) {}

    public function indexDocument(Document $document): void
    {
        $document->update(['embedding_status' => 'indexing']);

        $text = $this->extractor->extract($document);
        if ($text === null) {
            $document->update(['embedding_status' => null]);

            return;
        }

        $chunks = $this->chunker->chunk($text);
        if ($chunks === []) {
            $document->update(['embedding_status' => null]);

            return;
        }

        $this->deleteDocumentChunks($document, false);

        DB::transaction(function () use ($document, $chunks): void {
            foreach ($chunks as $chunk) {
                $embedding = $this->gemini->embed($chunk['content']);

                DB::table('document_chunks')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'document_id' => $document->id,
                    'user_id' => $document->user_id,
                    'chunk_index' => $chunk['index'],
                    'content' => $chunk['content'],
                    'embedding' => json_encode($embedding, JSON_THROW_ON_ERROR),
                    'token_count' => $chunk['token_count'],
                    'created_at' => now(),
                ]);
            }

            $document->update(['embedding_status' => 'indexed']);
        });
    }

    public function deleteDocumentChunks(Document $document, bool $clearStatus = true): void
    {
        DB::table('document_chunks')->where('document_id', $document->id)->delete();

        if ($clearStatus) {
            $document->update(['embedding_status' => null]);
        }
    }

    public function reindexDocument(Document $document): void
    {
        $this->deleteDocumentChunks($document, false);
        $this->indexDocument($document);
    }
}
