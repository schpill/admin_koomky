<?php

namespace App\Services;

use App\Exceptions\GeminiException;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    /** @return float[] */
    public function embed(string $text): array
    {
        $baseUrl = (string) config('services.gemini.url', 'https://generativelanguage.googleapis.com/v1beta');
        $model = (string) config('services.gemini.embedding_model', 'text-embedding-004');
        $key = (string) config('services.gemini.key');

        $response = Http::timeout(20)
            ->acceptJson()
            ->post("{$baseUrl}/models/{$model}:embedContent?key={$key}", [
                'content' => [
                    'parts' => [
                        ['text' => $text],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new GeminiException('Gemini embedding request failed: '.$response->status());
        }

        $values = $response->json('embedding.values', []);
        if (! is_array($values) || $values === []) {
            throw new GeminiException('Gemini embedding returned an empty vector.');
        }

        return array_map(static fn ($value): float => (float) $value, $values);
    }

    /** @param string[] $context */
    public function generate(string $prompt, array $context = []): string
    {
        $baseUrl = (string) config('services.gemini.url', 'https://generativelanguage.googleapis.com/v1beta');
        $model = (string) config('services.gemini.generation_model', 'gemini-2.5-flash');
        $key = (string) config('services.gemini.key');

        $fullPrompt = $prompt;
        if ($context !== []) {
            $fullPrompt .= "\n\nContexte:\n".implode("\n", $context);
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->post("{$baseUrl}/models/{$model}:generateContent?key={$key}", [
                'contents' => [[
                    'parts' => [[
                        'text' => $fullPrompt,
                    ]],
                ]],
            ]);

        if ($response->failed()) {
            throw new GeminiException('Gemini generation request failed: '.$response->status());
        }

        $text = (string) $response->json('candidates.0.content.parts.0.text', '');
        if ($text === '') {
            throw new GeminiException('Gemini generation returned an empty response.');
        }

        return $text;
    }
}
