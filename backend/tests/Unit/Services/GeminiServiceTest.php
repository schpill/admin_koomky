<?php

use App\Exceptions\GeminiException;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('embed returns float vector', function () {
    config(['services.gemini.key' => 'test-key']);

    Http::fake([
        '*' => Http::response([
            'embedding' => [
                'values' => [0.12, 0.45, -0.11],
            ],
        ], 200),
    ]);

    $service = new GeminiService;
    $vector = $service->embed('bonjour');

    expect($vector)->toBeArray()
        ->and($vector[0])->toBeFloat();
});

it('generate returns string content', function () {
    config(['services.gemini.key' => 'test-key']);

    Http::fake([
        '*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => 'Réponse générée',
                    ]],
                ],
            ]],
        ], 200),
    ]);

    $service = new GeminiService;
    $response = $service->generate('Q?');

    expect($response)->toBe('Réponse générée');
});

it('throws gemini exception on rate limit', function () {
    config(['services.gemini.key' => 'test-key']);

    Http::fake([
        '*' => Http::response(['error' => ['message' => 'rate limit']], 429),
    ]);

    $service = new GeminiService;

    expect(fn () => $service->embed('bonjour'))->toThrow(GeminiException::class);
});
