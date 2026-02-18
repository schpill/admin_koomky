<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('project profitability api returns project rows sorted by profit', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    $profitableProject = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'billing_type' => 'hourly',
        'hourly_rate' => 100,
        'name' => 'Profitable project',
    ]);
    $lowMarginProject = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'billing_type' => 'hourly',
        'hourly_rate' => 100,
        'name' => 'Low margin project',
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'project_id' => $profitableProject->id,
        'status' => 'paid',
        'total' => 1000,
    ]);
    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'project_id' => $lowMarginProject->id,
        'status' => 'paid',
        'total' => 500,
    ]);

    $profitableTask = Task::factory()->create(['project_id' => $profitableProject->id]);
    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $profitableTask->id,
        'duration_minutes' => 120,
    ]);

    $lowMarginTask = Task::factory()->create(['project_id' => $lowMarginProject->id]);
    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $lowMarginTask->id,
        'duration_minutes' => 240,
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'project_id' => $profitableProject->id,
        'client_id' => $client->id,
        'amount' => 100,
    ]);
    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'project_id' => $lowMarginProject->id,
        'client_id' => $client->id,
        'amount' => 250,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/project-profitability');

    $response->assertStatus(200);
    $rows = $response->json('data');

    expect($rows)->toBeArray();
    expect($rows)->toHaveCount(2);
    expect($rows[0]['profit'])->toBeGreaterThanOrEqual($rows[1]['profit']);
});

test('project expenses endpoint returns expenses linked to project', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'description' => 'Allocated expense',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/projects/'.$project->id.'/expenses')
        ->assertStatus(200)
        ->assertJsonPath('data.0.description', 'Allocated expense');
});
