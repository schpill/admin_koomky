<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('expense model supports relationships and common scopes', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);
    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Travel',
    ]);

    $inRangeExpense = Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'is_billable' => true,
        'date' => now()->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'date' => now()->subMonths(2)->toDateString(),
        'is_billable' => false,
    ]);

    expect($inRangeExpense->category)->not->toBeNull();
    expect($inRangeExpense->project)->not->toBeNull();
    expect($inRangeExpense->client)->not->toBeNull();

    expect(Expense::query()
        ->byDateRange(now()->subDays(1)->toDateString(), now()->addDays(1)->toDateString())
        ->count())->toBe(1);
    expect(Expense::query()->byCategory($category->id)->count())->toBe(2);
    expect(Expense::query()->byProject($project->id)->count())->toBe(1);
    expect(Expense::query()->billable()->count())->toBe(1);
});
