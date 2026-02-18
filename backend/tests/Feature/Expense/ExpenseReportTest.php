<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('expense report endpoint returns aggregated data', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'amount' => 100,
        'tax_amount' => 20,
        'is_billable' => true,
        'date' => now()->subDays(2)->toDateString(),
    ]);
    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'amount' => 50,
        'tax_amount' => 10,
        'is_billable' => false,
        'date' => now()->subDay()->toDateString(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/expenses/report?date_from='.now()->subWeek()->toDateString().'&date_to='.now()->toDateString());

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.total_expenses', 150.0)
        ->assertJsonPath('data.tax_total', 30.0);
});

test('expense report export returns csv', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Travel',
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'description' => 'Flight',
        'amount' => 320,
        'date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/expenses/report/export');

    $response->assertStatus(200);
    expect((string) $response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())->toContain('description');
    expect($response->streamedContent())->toContain('Flight');
});
