<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('project invoice generation can include billable project expenses as line items', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'billing_type' => 'hourly',
        'hourly_rate' => 100,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'task_id' => $task->id,
        'duration_minutes' => 120,
        'is_billed' => false,
    ]);

    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
    ]);

    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'description' => 'On-site transport',
        'amount' => 75,
        'is_billable' => true,
        'status' => 'approved',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects/'.$project->id.'/generate-invoice', [
            'include_billable_expenses' => true,
        ]);

    $response->assertStatus(201);
    $invoiceId = (string) $response->json('data.id');

    $lineItems = collect($response->json('data.line_items', []));

    expect($lineItems->pluck('description')->implode(' '))->toContain('On-site transport');

    $this->assertDatabaseHas('line_items', [
        'documentable_id' => $invoiceId,
        'description' => 'Billable expense - '.$expense->description,
    ]);
});
