<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\ExpenseReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['base_currency' => 'EUR']);
    $this->travel = ExpenseCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Travel']);
    $this->software = ExpenseCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Software']);
    $this->service = app(ExpenseReportService::class);
});

test('expense report service aggregates totals and breakdowns in base currency', function () {
    Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $this->travel->id,
        'amount' => 100, // EUR
        'currency' => 'EUR',
        'base_currency_amount' => 100.00,
        'tax_amount' => 20,
    ]);
    Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $this->software->id,
        'amount' => 50, // USD
        'currency' => 'USD',
        'base_currency_amount' => 60.00, // 1 USD = 1.2 EUR
        'tax_amount' => 10,
    ]);

    $report = $this->service->build($this->user);

    // Total should be 100 EUR + 60 EUR (converted from 50 USD)
    expect($report['total_expenses'])->toBe(160.0);
    // Tax should be 20 EUR + 12 EUR (10 USD * 1.2)
    expect($report['tax_total'])->toBe(32.0);

    $travelCategory = collect($report['by_category'])->firstWhere('category', 'Travel');
    $softwareCategory = collect($report['by_category'])->firstWhere('category', 'Software');

    expect($travelCategory['total'])->toBe(100.0);
    expect($softwareCategory['total'])->toBe(60.0);
});

test('expense report service handles empty datasets', function () {
    $report = $this->service->build($this->user);

    expect($report['total_expenses'])->toBe(0.0);
    expect($report['by_category'])->toBeArray()->toBeEmpty();
    expect($report['by_project'])->toBeArray()->toBeEmpty();
});

test('expense report service filters correctly', function () {
    Expense::factory()->create(['user_id' => $this->user->id, 'expense_category_id' => $this->travel->id, 'amount' => 100]);
    Expense::factory()->create(['user_id' => $this->user->id, 'expense_category_id' => $this->software->id, 'amount' => 50]);

    $report = $this->service->build($this->user, ['expense_category_id' => $this->travel->id]);

    expect($report['total_expenses'])->toBe(100.0);
    expect($report['count'])->toBe(1);
    expect($report['by_category'])->toHaveCount(1);
});
