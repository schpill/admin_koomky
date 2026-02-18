<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profit loss api returns aggregated profit and margin data', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'total' => 500,
        'currency' => 'EUR',
        'issue_date' => now()->subDays(3)->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'amount' => 150,
        'date' => now()->subDays(2)->toDateString(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/profit-loss?date_from='.now()->subMonth()->toDateString().'&date_to='.now()->toDateString())
        ->assertStatus(200)
        ->assertJsonPath('data.revenue', 500.0)
        ->assertJsonPath('data.expenses', 150.0)
        ->assertJsonPath('data.profit', 350.0);
});
