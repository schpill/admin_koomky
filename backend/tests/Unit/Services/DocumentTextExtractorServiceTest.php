<?php

use App\Models\Document;
use App\Services\DocumentTextExtractorService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('extracts txt content', function () {
    $path = 'documents/test.txt';
    Storage::disk('local')->put($path, "ligne 1\nligne 2");

    $document = new Document([
        'storage_disk' => 'local',
        'storage_path' => $path,
        'mime_type' => 'text/plain',
    ]);

    $service = new DocumentTextExtractorService;
    $text = $service->extract($document);

    expect($text)->toContain('ligne 1');
});

it('returns null for unsupported mime', function () {
    $document = new Document([
        'mime_type' => 'application/zip',
        'storage_disk' => 'local',
        'storage_path' => 'documents/unsupported.zip',
    ]);

    $service = new DocumentTextExtractorService;
    expect($service->extract($document))->toBeNull();
});
