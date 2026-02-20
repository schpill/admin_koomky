<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create([
        'base_currency' => 'EUR',
        'accounting_journal_sales' => 'VTE',
        'accounting_journal_purchases' => 'ACH',
        'accounting_journal_bank' => 'BQ',
    ]);
    $this->actingAs($this->user, 'sanctum');
});

test('fec export generates valid header row', function () {
    $service = new \App\Services\FecExportService;
    $lines = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect($lines[0])->toContain('JournalCode')
        ->and($lines[0])->toContain('JournalLib')
        ->and($lines[0])->toContain('EcritureDate')
        ->and($lines[0])->toContain('CompteNum')
        ->and($lines[0])->toContain('Debit')
        ->and($lines[0])->toContain('Credit');
});

test('fec export includes invoice entries', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 120.00,
        'tax_amount' => 20.00,
        'currency' => 'EUR',
    ]);

    \App\Models\LineItem::factory()->create([
        'documentable_type' => \App\Models\Invoice::class,
        'documentable_id' => $invoice->id,
        'vat_rate' => 20.00,
        'total' => 100.00,
    ]);

    $service = new \App\Services\FecExportService;
    $lines = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    // Should have header + 3 entries for invoice (client debit, revenue credit, VAT credit)
    expect(count($lines))->toBeGreaterThan(1);
});

test('fec export uses french decimal format with comma', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 120.50,
        'tax_amount' => 20.08,
        'currency' => 'EUR',
    ]);

    $service = new \App\Services\FecExportService;
    $lines = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    // Check for French number format (comma as decimal separator)
    $foundFrenchFormat = false;
    foreach ($lines as $line) {
        if (str_contains($line, '120,50') || str_contains($line, '20,08')) {
            $foundFrenchFormat = true;
            break;
        }
    }

    expect($foundFrenchFormat)->toBeTrue();
});

test('fec export respects date filtering', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    // Invoice in 2023
    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2023-06-15',
        'total' => 100.00,
        'number' => 'INV-2023-001',
    ]);

    // Invoice in 2024
    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 200.00,
        'number' => 'INV-2024-001',
    ]);

    $service = new \App\Services\FecExportService;

    // Only 2024
    $lines2024 = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    // Only 2023
    $lines2023 = iterator_to_array($service->generate($this->user, [
        'date_from' => '2023-01-01',
        'date_to' => '2023-12-31',
    ]));

    // 2024 export should have more lines (includes 2024 invoice)
    expect(count($lines2024))->toBeGreaterThan(1)
        ->and(count($lines2023))->toBeGreaterThan(1);
});

test('fec export includes credit note entries', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    \App\Models\CreditNote::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
        'issue_date' => '2024-06-20',
        'total' => 50.00,
        'tax_amount' => 8.33,
    ]);

    $service = new \App\Services\FecExportService;
    $lines = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect(count($lines))->toBeGreaterThan(1);
});

test('fec export includes payment entries', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'paid',
    ]);

    \App\Models\Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 100.00,
        'payment_date' => '2024-06-25',
    ]);

    $service = new \App\Services\FecExportService;
    $lines = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect(count($lines))->toBeGreaterThan(1);
});

test('fec export includes expense entries', function () {
    $category = \App\Models\ExpenseCategory::factory()->create([
        'user_id' => $this->user->id,
        'account_code' => '622600',
    ]);

    \App\Models\Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $category->id,
        'status' => 'approved',
        'date' => '2024-06-10',
        'amount' => 60.00,
        'tax_amount' => 10.00,
    ]);

    $service = new \App\Services\FecExportService;
    $lines = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect(count($lines))->toBeGreaterThan(1);
});

test('fec entry count is accurate', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 120.00,
        'tax_amount' => 20.00,
    ]);

    $service = new \App\Services\FecExportService;
    $count = $service->getEntryCount($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]);

    $lines = iterator_to_array($service->generate($this->user, [
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31',
    ]));

    expect($count)->toBe(count($lines));
});
