<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ProjectProfitabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('project profitability service computes revenue time cost expenses and profit', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'billing_type' => 'hourly',
        'hourly_rate' => 100,
    ]);
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'project_id' => $project->id,
        'status' => 'paid',
        'total' => 1000,
        'currency' => 'EUR',
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $task->id,
        'duration_minutes' => 180,
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'amount' => 250,
    ]);

    $service = app(ProjectProfitabilityService::class);
    $report = $service->build($user, []);

    expect($report)->toHaveCount(1);
    expect($report[0]['project_id'])->toBe($project->id);
    expect($report[0]['revenue'])->toBe(1000.0);
    expect($report[0]['time_cost'])->toBe(300.0);
    expect($report[0]['expenses'])->toBe(250.0);
    expect($report[0]['profit'])->toBe(450.0);
});

test('project profitability returns zeroed metrics for project without data', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'billing_type' => 'hourly',
        'hourly_rate' => 80,
    ]);

    $service = app(ProjectProfitabilityService::class);
    $report = $service->build($user, []);

    expect($report)->toHaveCount(1);
    expect($report[0]['project_id'])->toBe($project->id);
    expect($report[0]['profit'])->toBe(0.0);
});
