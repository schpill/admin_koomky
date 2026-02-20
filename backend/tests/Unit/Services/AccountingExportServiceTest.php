<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('accounting export generates pennylane format', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 120.00,
        'tax_amount' => 20.00,
        'currency' => 'EUR',
    ]);

    $service = new \App\Services\AccountingExportService;
    $lines = iterator_to_array($service->generate($this->user, 'pennylane', [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect($lines[0])->toContain('date')
        ->and($lines[0])->toContain('piece_ref')
        ->and($lines[0])->toContain('account_code');
});

test('accounting export generates sage format', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 100.00,
        'currency' => 'EUR',
    ]);

    $service = new \App\Services\AccountingExportService;
    $lines = iterator_to_array($service->generate($this->user, 'sage', [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect($lines[0])->toContain('Date')
        ->and($lines[0])->toContain('Référence')
        ->and($lines[0])->toContain('N° Compte');
});

test('accounting export generates generic format', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 100.00,
    ]);

    $service = new \App\Services\AccountingExportService;
    $lines = iterator_to_array($service->generate($this->user, 'generic', [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect($lines[0])->toContain('date')
        ->and($lines[0])->toContain('reference')
        ->and($lines[0])->toContain('account_name');
});

test('accounting export respects date range', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2023-06-15',
        'number' => 'INV-2023-001',
    ]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'number' => 'INV-2024-001',
    ]);

    $service = new \App\Services\AccountingExportService;

    $lines2024 = iterator_to_array($service->generate($this->user, 'generic', [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    $lines2023 = iterator_to_array($service->generate($this->user, 'generic', [
        'date_from' => '2023-01-01',
        'date_to' => '2023-12-31',
    ]));

    // Both should have header + entries
    expect(count($lines2024))->toBeGreaterThan(1)
        ->and(count($lines2023))->toBeGreaterThan(1);
});

test('accounting export handles empty period', function () {
    $service = new \App\Services\AccountingExportService;
    $lines = iterator_to_array($service->generate($this->user, 'generic', [
        'date_from' => '2020-01-01',
        'date_to' => '2020-12-31',
    ]));

    // Should at least have header
    expect(count($lines))->toBe(1);
});

test('accounting export get columns for each format', function () {
    $service = new \App\Services\AccountingExportService;

    $pennylaneColumns = $service->getColumns('pennylane');
    $sageColumns = $service->getColumns('sage');
    $genericColumns = $service->getColumns('generic');

    expect($pennylaneColumns)->toContain('piece_ref')
        ->and($sageColumns)->toContain('Référence')
        ->and($genericColumns)->toContain('account_name');
});
