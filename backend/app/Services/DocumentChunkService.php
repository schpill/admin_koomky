<?php

namespace App\Services;

class DocumentChunkService
{
    private const MIN_TOKENS = 100;

    private const MAX_TOKENS = 512;

    private const OVERLAP_TOKENS = 64;

    public function chunk(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split('/\n\s*\n/u', $text) ?: [];
        $paragraphs = array_values(array_filter(array_map(static fn ($p) => trim($p), $paragraphs)));
        if ($paragraphs === []) {
            $paragraphs = [$text];
        }

        $chunks = [];
        $buffer = '';
        $index = 0;

        foreach ($paragraphs as $paragraph) {
            $candidate = trim($buffer === '' ? $paragraph : $buffer."\n\n".$paragraph);
            $candidateTokens = $this->estimateTokens($candidate);

            if ($candidateTokens <= self::MAX_TOKENS) {
                $buffer = $candidate;

                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $this->buildChunk($index++, $buffer);
                $buffer = '';
            }

            foreach ($this->splitByWordLimit($paragraph, self::MAX_TOKENS, self::OVERLAP_TOKENS) as $piece) {
                $chunks[] = $this->buildChunk($index++, $piece);
            }
        }

        if ($buffer !== '') {
            $chunks[] = $this->buildChunk($index++, $buffer);
        }

        return $this->mergeSmallChunks($chunks);
    }

    private function splitByWordLimit(string $text, int $maxTokens, int $overlapTokens): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        if ($words === []) {
            return [];
        }

        $maxWords = max(1, (int) floor($maxTokens / 1.3));
        $overlapWords = max(0, (int) floor($overlapTokens / 1.3));

        $chunks = [];
        $start = 0;
        $count = count($words);

        while ($start < $count) {
            $end = min($count, $start + $maxWords);
            $slice = array_slice($words, $start, $end - $start);
            $chunks[] = implode(' ', $slice);

            if ($end >= $count) {
                break;
            }

            $start = max($start + 1, $end - $overlapWords);
        }

        return $chunks;
    }

    private function mergeSmallChunks(array $chunks): array
    {
        if ($chunks === []) {
            return [];
        }

        $merged = [];
        foreach ($chunks as $chunk) {
            if ($merged === []) {
                $merged[] = $chunk;

                continue;
            }

            $lastIndex = count($merged) - 1;
            $last = $merged[$lastIndex];

            if ($last['token_count'] < self::MIN_TOKENS) {
                $combinedContent = trim($last['content']."\n\n".$chunk['content']);
                $merged[$lastIndex] = [
                    'index' => $last['index'],
                    'content' => $combinedContent,
                    'token_count' => $this->estimateTokens($combinedContent),
                ];

                continue;
            }

            $merged[] = $chunk;
        }

        foreach ($merged as $i => $chunk) {
            $merged[$i]['index'] = $i;
        }

        return $merged;
    }

    private function buildChunk(int $index, string $content): array
    {
        return [
            'index' => $index,
            'content' => trim($content),
            'token_count' => $this->estimateTokens($content),
        ];
    }

    private function estimateTokens(string $text): int
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];

        return max(1, (int) ceil(count($words) * 1.3));
    }
}
