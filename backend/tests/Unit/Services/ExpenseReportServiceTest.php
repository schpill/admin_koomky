<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use App\Services\ExpenseReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('expense report service aggregates totals and breakdowns', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);
    $travel = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Travel',
    ]);
    $software = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Software',
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $travel->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'amount' => 100,
        'tax_amount' => 20,
        'is_billable' => true,
        'date' => now()->subDays(2)->toDateString(),
    ]);
    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $software->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'amount' => 50,
        'tax_amount' => 10,
        'is_billable' => false,
        'date' => now()->subDay()->toDateString(),
    ]);

    $service = app(ExpenseReportService::class);

    $report = $service->build($user, [
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->toDateString(),
    ]);

    expect($report['total_expenses'])->toBe(150.0);
    expect($report['tax_total'])->toBe(30.0);
    expect($report['billable_split']['billable'])->toBe(100.0);
    expect($report['billable_split']['non_billable'])->toBe(50.0);
    expect($report['by_category'])->toHaveCount(2);
    expect($report['by_project'])->toHaveCount(1);
});

test('expense report service handles empty datasets', function () {
    $user = User::factory()->create();

    $service = app(ExpenseReportService::class);
    $report = $service->build($user, []);

    expect($report['total_expenses'])->toBe(0.0);
    expect($report['by_category'])->toBeArray();
    expect($report['by_project'])->toBeArray();
});
