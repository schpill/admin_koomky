<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\ImportService;
use App\Services\ReferenceGeneratorService;

beforeEach(function () {
    $this->service = new ImportService(new ReferenceGeneratorService);
});

it('generates a CSV template with correct headers', function () {
    $csv = $this->service->generateCsvTemplate();

    expect($csv)->toContain('company_name');
    expect($csv)->toContain('first_name');
    expect($csv)->toContain('last_name');
    expect($csv)->toContain('email');
    expect($csv)->toContain('phone');
    expect($csv)->toContain('vat_number');
    expect($csv)->toContain('website');
    expect($csv)->toContain('address');
    expect($csv)->toContain('city');
    expect($csv)->toContain('postal_code');
    expect($csv)->toContain('country');
    expect($csv)->toContain('notes');
});

it('generates a CSV template with example row', function () {
    $csv = $this->service->generateCsvTemplate();

    expect($csv)->toContain('Acme Corporation');
    expect($csv)->toContain('contact@acme.com');
});

it('generates a non-empty CSV template', function () {
    $csv = $this->service->generateCsvTemplate();
    $lines = array_filter(explode("\n", $csv));

    // Header + 1 example row
    expect(count($lines))->toBeGreaterThanOrEqual(2);
});

it('imports clients from CSV file', function () {
    \Illuminate\Support\Facades\Bus::fake();

    $user = User::factory()->create();

    // Create a temp CSV file
    $csvContent = "company_name,first_name,last_name,email,phone,vat_number,website,address,city,postal_code,country,notes\n";
    $csvContent .= "Acme Corp,John,Doe,john@acme.com,+33123,FR123,https://acme.com,123 Street,Paris,75001,France,Note\n";
    $csvContent .= "Beta Inc,Jane,Smith,jane@beta.com,+33456,FR456,https://beta.com,456 Ave,Lyon,69001,France,Other\n";

    $filePath = storage_path('app/test_import.csv');

    // Ensure the directory exists
    $directory = dirname($filePath);
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    file_put_contents($filePath, $csvContent);

    $result = $this->service->importClientsFromCsv($user, $filePath);

    expect($result)->toHaveKeys(['total', 'success', 'failed', 'batch_id']);
    expect($result['total'])->toBe(2);

    // Cleanup
    @unlink($filePath);
});
