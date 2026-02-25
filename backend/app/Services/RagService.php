<?php

namespace App\Services;

use App\Models\RagUsageLog;

class RagService
{
    public function __construct(
        protected VectorSearchService $search,
        protected GeminiService $gemini
    ) {}

    /** @return array<string, mixed> */
    public function answer(string $question, string $userId, ?string $clientId = null): array
    {
        $start = microtime(true);
        $chunks = $this->search->search($question, $userId, 5, $clientId);

        if ($chunks->isEmpty()) {
            return [
                'answer' => 'Documents insuffisants pour répondre précisément à cette question.',
                'sources' => [],
                'tokens_used' => 0,
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        }

        $context = $chunks->map(fn ($chunk) => "[source {$chunk->document_id}#{$chunk->chunk_index}] {$chunk->content}")->all();

        $prompt = 'Tu es un assistant basé sur les documents fournis. Réponds uniquement à partir du contexte ci-dessous. '
            ."Si la réponse n'est pas dans les documents, dis-le clairement. Cite les sources.";

        $answer = $this->gemini->generate($prompt."\nQuestion: {$question}", $context);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);
        $tokens = (int) ceil((str_word_count($question) + str_word_count($answer)) * 1.3);

        $sources = $chunks->map(fn ($chunk) => [
            'document_id' => $chunk->document_id,
            'title' => $chunk->title ?? null,
            'chunk_index' => $chunk->chunk_index,
            'score' => (float) ($chunk->score ?? 0),
        ])->values()->all();

        RagUsageLog::query()->create([
            'user_id' => $userId,
            'question' => $question,
            'chunks_used' => $sources,
            'tokens_used' => $tokens,
            'latency_ms' => $latencyMs,
            'created_at' => now(),
        ]);

        return [
            'answer' => $answer,
            'sources' => $sources,
            'tokens_used' => $tokens,
            'latency_ms' => $latencyMs,
        ];
    }
}
