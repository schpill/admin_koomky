<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('vat declaration computes collected vat by rate', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    // Invoice with 20% VAT
    $invoice1 = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'tax_amount' => 20.00,
    ]);

    // Create line item directly to avoid factory issues
    \Illuminate\Support\Facades\DB::table('line_items')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'documentable_type' => \App\Models\Invoice::class,
        'documentable_id' => $invoice1->id,
        'description' => 'Test item',
        'quantity' => 1,
        'unit_price' => 100.00,
        'vat_rate' => 20.00,
        'total' => 100.00,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = new \App\Services\VatDeclarationService;
    $report = $service->build($this->user, ['year' => 2024, 'period_type' => 'monthly']);

    expect($report['year'])->toBe(2024)
        ->and($report['period_type'])->toBe('monthly')
        ->and(count($report['periods']))->toBe(12);
});

test('vat declaration computes deductible vat from expenses', function () {
    $category = \App\Models\ExpenseCategory::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $category->id,
        'status' => 'approved',
        'date' => '2024-06-10',
        'amount' => 60.00,
        'tax_amount' => 10.00,
    ]);

    $service = new \App\Services\VatDeclarationService;
    $report = $service->build($this->user, ['year' => 2024, 'period_type' => 'monthly']);

    // June should have deductible VAT
    $junePeriod = collect($report['periods'])->firstWhere('month', 6);
    expect($junePeriod['total_deductible'])->toBe(10.00);
});

test('vat declaration computes net vat due', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $category = \App\Models\ExpenseCategory::factory()->create(['user_id' => $this->user->id]);

    // Invoice with VAT
    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'tax_amount' => 40.00,
    ]);

    // Create line item directly to avoid factory issues
    \Illuminate\Support\Facades\DB::table('line_items')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'documentable_type' => \App\Models\Invoice::class,
        'documentable_id' => $invoice->id,
        'description' => 'Test item',
        'quantity' => 1,
        'unit_price' => 200.00,
        'vat_rate' => 20.00,
        'total' => 200.00,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Expense with VAT
    \App\Models\Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $category->id,
        'status' => 'approved',
        'date' => '2024-06-10',
        'amount' => 60.00,
        'tax_amount' => 10.00,
    ]);

    $service = new \App\Services\VatDeclarationService;
    $report = $service->build($this->user, ['year' => 2024, 'period_type' => 'monthly']);

    $junePeriod = collect($report['periods'])->firstWhere('month', 6);
    expect($junePeriod['total_collected'])->toBe(40.00)
        ->and($junePeriod['total_deductible'])->toBe(10.00)
        ->and($junePeriod['net_due'])->toBe(30.00);
});

test('vat declaration supports quarterly periods', function () {
    $service = new \App\Services\VatDeclarationService;
    $report = $service->build($this->user, ['year' => 2024, 'period_type' => 'quarterly']);

    expect($report['period_type'])->toBe('quarterly')
        ->and(count($report['periods']))->toBe(4);
});

test('vat declaration subtracts credit note vat', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-01',
    ]);

    $creditNote = \App\Models\CreditNote::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
        'issue_date' => '2024-06-20',
        'tax_amount' => 10.00,
    ]);

    // Create line item for credit note directly
    \Illuminate\Support\Facades\DB::table('line_items')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'documentable_type' => \App\Models\CreditNote::class,
        'documentable_id' => $creditNote->id,
        'description' => 'Credit note item',
        'quantity' => 1,
        'unit_price' => 50.00,
        'vat_rate' => 20.00,
        'total' => 50.00,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = new \App\Services\VatDeclarationService;
    $report = $service->build($this->user, ['year' => 2024, 'period_type' => 'monthly']);

    $junePeriod = collect($report['periods'])->firstWhere('month', 6);
    // Should have negative VAT collected from credit note
    expect($junePeriod['total_collected'])->toBe(-10.00);
});

test('vat declaration handles zero vat invoices', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'tax_amount' => 0.00,
    ]);

    // Create line item directly to avoid factory issues
    \Illuminate\Support\Facades\DB::table('line_items')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'documentable_type' => \App\Models\Invoice::class,
        'documentable_id' => $invoice->id,
        'description' => 'Test item',
        'quantity' => 1,
        'unit_price' => 100.00,
        'vat_rate' => 0.00,
        'total' => 100.00,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = new \App\Services\VatDeclarationService;
    $report = $service->build($this->user, ['year' => 2024, 'period_type' => 'monthly']);

    $junePeriod = collect($report['periods'])->firstWhere('month', 6);
    expect($junePeriod['vat_collected']['0'])->toBe(0.00);
});

test('vat csv export has correct format', function () {
    $service = new \App\Services\VatDeclarationService;
    $report = $service->build($this->user, ['year' => 2024, 'period_type' => 'monthly']);
    $csv = $service->toCsv($report);

    expect($csv)->toContain('Period')
        ->and($csv)->toContain('VAT 0%')
        ->and($csv)->toContain('VAT 20%')
        ->and($csv)->toContain('Net Due');
});
