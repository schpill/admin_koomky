<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;
use App\Services\ExportService;

beforeEach(function () {
    $this->service = new ExportService;

    // Ensure temp directory exists
    $tempDir = storage_path('app/temp');
    if (! is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
});

afterEach(function () {
    // Clean up exported files
    $files = glob(storage_path('app/temp/*.csv'));
    foreach ($files as $file) {
        @unlink($file);
    }
});

it('exports clients to CSV file', function () {
    $user = User::factory()->create();
    $clients = Client::factory()->count(3)->create(['user_id' => $user->id]);

    $filePath = $this->service->exportClientsToCsv($clients, 'test_export');

    expect($filePath)->toEndWith('test_export.csv');
    expect(file_exists($filePath))->toBeTrue();
});

it('includes headers in CSV export', function () {
    $user = User::factory()->create();
    $clients = Client::factory()->count(1)->create(['user_id' => $user->id]);

    $filePath = $this->service->exportClientsToCsv($clients, 'test_headers');
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);

    expect($lines[0])->toContain('Reference');
    expect($lines[0])->toContain('Name');
    expect($lines[0])->toContain('Email');
    expect($lines[0])->toContain('Status');
});

it('exports correct number of rows', function () {
    $user = User::factory()->create();
    $clients = Client::factory()->count(5)->create(['user_id' => $user->id]);

    $filePath = $this->service->exportClientsToCsv($clients, 'test_rows');
    $content = file_get_contents($filePath);
    $lines = array_filter(explode("\n", $content));

    // 1 header + 5 data rows
    expect(count($lines))->toBe(6);
});

it('handles empty collection', function () {
    $filePath = $this->service->exportClientsToCsv(collect(), 'test_empty');
    $content = file_get_contents($filePath);
    $lines = array_filter(explode("\n", $content));

    // Only header row
    expect(count($lines))->toBe(1);
});

it('replaces newlines in text fields', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'notes' => "Line one\nLine two\rLine three",
    ]);

    $filePath = $this->service->exportClientsToCsv(collect([$client]), 'test_newlines');
    $content = file_get_contents($filePath);

    expect($content)->not->toContain("\nLine two");
});
