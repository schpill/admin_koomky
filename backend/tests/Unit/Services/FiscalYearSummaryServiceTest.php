<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create([
        'base_currency' => 'EUR',
        'fiscal_year_start_month' => 1,
    ]);
    $this->actingAs($this->user, 'sanctum');
});

test('fiscal year summary computes revenue', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'issue_date' => '2024-06-15',
        'total' => 1000.00,
        'base_currency_total' => 1000.00,
    ]);

    $service = new \App\Services\FiscalYearSummaryService;
    $summary = $service->build($this->user, ['year' => 2024]);

    expect($summary['year'])->toBe(2024)
        ->and($summary['revenue'])->toHaveKey('total');
});

test('fiscal year summary computes expenses', function () {
    $category = \App\Models\ExpenseCategory::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $category->id,
        'status' => 'approved',
        'date' => '2024-06-10',
        'amount' => 500.00,
        'base_currency_amount' => 500.00,
    ]);

    $service = new \App\Services\FiscalYearSummaryService;
    $summary = $service->build($this->user, ['year' => 2024]);

    expect($summary['expenses']['total'])->toBe(500.00);
});

test('fiscal year summary computes net profit', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $category = \App\Models\ExpenseCategory::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'issue_date' => '2024-06-15',
        'total' => 1000.00,
        'base_currency_total' => 1000.00,
    ]);

    \App\Models\Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $category->id,
        'status' => 'approved',
        'date' => '2024-06-10',
        'amount' => 400.00,
        'base_currency_amount' => 400.00,
    ]);

    $service = new \App\Services\FiscalYearSummaryService;
    $summary = $service->build($this->user, ['year' => 2024]);

    expect($summary['net_profit'])->toBe(600.00)
        ->and($summary['margin_percent'])->toBe(60.00);
});

test('fiscal year summary computes vat position', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $category = \App\Models\ExpenseCategory::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'tax_amount' => 40.00,
    ]);

    \App\Models\Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $category->id,
        'status' => 'approved',
        'date' => '2024-06-10',
        'tax_amount' => 10.00,
    ]);

    $service = new \App\Services\FiscalYearSummaryService;
    $summary = $service->build($this->user, ['year' => 2024]);

    expect($summary['vat_position']['vat_collected'])->toBe(40.00)
        ->and($summary['vat_position']['vat_deductible'])->toBe(10.00)
        ->and($summary['vat_position']['net_due'])->toBe(30.00);
});

test('fiscal year summary computes outstanding receivables', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'due_date' => '2024-07-15',
        'total' => 1000.00,
        'base_currency_total' => 1000.00,
    ]);

    $service = new \App\Services\FiscalYearSummaryService;
    $summary = $service->build($this->user, ['year' => 2024]);

    expect($summary['outstanding_receivables']['total'])->toBe(1000.00)
        ->and($summary['outstanding_receivables']['invoice_count'])->toBe(1);
});

test('fiscal year summary respects fiscal year start month', function () {
    $user = \App\Models\User::factory()->create([
        'base_currency' => 'EUR',
        'fiscal_year_start_month' => 7, // July start
    ]);

    $service = new \App\Services\FiscalYearSummaryService;
    $summary = $service->build($user, ['year' => 2024]);

    expect($summary['fiscal_year_start_month'])->toBe(7)
        ->and($summary['date_from'])->toBe('2024-07-01');
});
