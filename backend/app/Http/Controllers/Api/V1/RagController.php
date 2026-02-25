<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rag\AskRagRequest;
use App\Jobs\ProcessDocumentEmbeddingJob;
use App\Models\Document;
use App\Services\RagService;
use App\Services\VectorSearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RagController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RagService $rag,
        protected VectorSearchService $search
    ) {}

    public function ask(AskRagRequest $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->rag->answer(
            (string) $request->string('question'),
            (string) $user->id,
            $request->string('client_id')->toString() ?: null
        );

        return $this->success($result, 'RAG response generated');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'max:1000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'client_id' => ['nullable', 'uuid'],
        ]);

        $user = $request->user();

        $results = $this->search->search(
            (string) $request->query('q'),
            (string) $user->id,
            (int) $request->integer('limit', 5),
            $request->query('client_id') ? (string) $request->query('client_id') : null
        );

        return $this->success($results->values(), 'Semantic search completed');
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $documents = Document::query()
            ->where('user_id', $user->id)
            ->select(['id', 'title', 'mime_type', 'client_id', 'embedding_status', 'updated_at', 'created_at'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return $this->success($documents, 'RAG status retrieved');
    }

    public function reindex(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        $document->update(['embedding_status' => 'pending']);
        ProcessDocumentEmbeddingJob::dispatch($document);

        return $this->success(null, 'Reindex job dispatched', 202);
    }
}
