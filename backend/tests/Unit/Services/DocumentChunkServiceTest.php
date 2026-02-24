<?php

use App\Services\DocumentChunkService;
use Tests\TestCase;

uses(TestCase::class);

it('creates one chunk for short text', function () {
    $service = new DocumentChunkService;
    $chunks = $service->chunk("Paragraphe court.");

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]['index'])->toBe(0)
        ->and($chunks[0]['token_count'])->toBeGreaterThan(0);
});

it('splits long text into multiple chunks with overlap', function () {
    $service = new DocumentChunkService;
    $text = implode(' ', array_fill(0, 1400, 'mot'));

    $chunks = $service->chunk($text);

    expect(count($chunks))->toBeGreaterThan(1);
    expect($chunks[0]['token_count'])->toBeLessThanOrEqual(512)
        ->and($chunks[1]['token_count'])->toBeLessThanOrEqual(512);
});
