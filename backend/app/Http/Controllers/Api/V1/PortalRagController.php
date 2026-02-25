<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RagService;
use App\Services\VectorSearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalRagController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RagService $rag,
        protected VectorSearchService $search
    ) {}

    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $client = $request->attributes->get('portal_client');
        abort_unless($client !== null, 401);

        $result = $this->rag->answer(
            (string) $request->string('question'),
            (string) $client->user_id,
            (string) $client->id
        );

        return $this->success($result, 'Portal RAG response generated');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'max:1000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $client = $request->attributes->get('portal_client');
        abort_unless($client !== null, 401);

        $results = $this->search->search(
            (string) $request->query('q'),
            (string) $client->user_id,
            (int) $request->integer('limit', 5),
            (string) $client->id
        );

        return $this->success($results->values(), 'Portal semantic search completed');
    }

    public function status(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        abort_unless($client !== null, 401);

        $count = \App\Models\Document::query()
            ->where('user_id', $client->user_id)
            ->where(function ($query) use ($client): void {
                $query->whereNull('client_id')->orWhere('client_id', $client->id);
            })
            ->where('embedding_status', 'indexed')
            ->count();

        return $this->success([
            'available' => $count > 0,
            'indexed_documents' => $count,
        ], 'Portal RAG availability retrieved');
    }
}
