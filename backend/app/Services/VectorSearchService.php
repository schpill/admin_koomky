<?php

namespace App\Services;

use App\Models\DocumentChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VectorSearchService
{
    public function __construct(protected GeminiService $gemini) {}

    /** @return Collection<int, mixed> */
    public function search(string $query, string $userId, int $topK = 5, ?string $clientId = null): Collection
    {
        $vector = $this->gemini->embed($query);

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $vectorLiteral = '['.implode(',', array_map(static fn ($v) => (string) $v, $vector)).']';

            $sql = 'SELECT c.id, c.document_id, c.chunk_index, c.content, c.token_count, d.title, d.client_id,
                        1 - (c.embedding <=> ?::vector) AS score
                    FROM document_chunks c
                    INNER JOIN documents d ON d.id = c.document_id
                    WHERE d.user_id = ?';

            $bindings = [$vectorLiteral, $userId];

            if ($clientId !== null) {
                $sql .= ' AND d.client_id = ?';
                $bindings[] = $clientId;
            }

            $sql .= ' ORDER BY c.embedding <=> ?::vector LIMIT ?';
            $bindings[] = $vectorLiteral;
            $bindings[] = $topK;

            return collect(DB::select($sql, $bindings));
        }

        $queryBuilder = DocumentChunk::query()
            ->select('document_chunks.*', 'documents.title', 'documents.client_id')
            ->join('documents', 'documents.id', '=', 'document_chunks.document_id')
            ->where('documents.user_id', $userId);

        if ($clientId !== null) {
            $queryBuilder->where('documents.client_id', $clientId);
        }

        return $queryBuilder->get()->map(function (DocumentChunk $chunk) use ($vector) {
            $score = $this->cosineSimilarity($vector, (array) $chunk->embedding);
            $chunk->score = $score;

            return $chunk;
        })->sortByDesc('score')->take($topK)->values();
    }

    /**
     * @param  float[]  $a
     * @param  float[]  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $normA += $x * $x;
            $normB += $y * $y;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $dot / (sqrt($normA) * sqrt($normB))));
    }
}
