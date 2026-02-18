<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ProfitLossReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('profit loss report service computes revenue expenses profit and margin', function () {
    $user = User::factory()->create(['base_currency' => 'EUR']);
    $client = Client::factory()->create(['user_id' => $user->id]);
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'total' => 300,
        'currency' => 'EUR',
        'issue_date' => now()->subDays(10)->toDateString(),
    ]);
    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'total' => 200,
        'currency' => 'EUR',
        'issue_date' => now()->subDays(4)->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'amount' => 120,
        'date' => now()->subDays(6)->toDateString(),
    ]);
    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'amount' => 30,
        'date' => now()->subDays(3)->toDateString(),
    ]);

    $service = app(ProfitLossReportService::class);
    $report = $service->build($user, [
        'date_from' => now()->subMonth()->toDateString(),
        'date_to' => now()->toDateString(),
    ]);

    expect($report['revenue'])->toBe(500.0);
    expect($report['expenses'])->toBe(150.0);
    expect($report['profit'])->toBe(350.0);
    expect($report['margin'])->toBe(70.0);
    expect($report['by_month'])->toBeArray();
});

test('profit loss report handles zero revenue safely', function () {
    $user = User::factory()->create();

    $service = app(ProfitLossReportService::class);
    $report = $service->build($user, []);

    expect($report['revenue'])->toBe(0.0);
    expect($report['margin'])->toBe(0.0);
});
