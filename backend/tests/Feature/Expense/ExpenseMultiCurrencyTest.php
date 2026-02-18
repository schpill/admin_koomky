<?php

use App\Models\ExchangeRate;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('expense creation computes base currency amount using exchange rates', function () {
    $user = User::factory()->create([
        'base_currency' => 'EUR',
    ]);
    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
    ]);

    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'target_currency' => 'EUR',
        'rate' => 1.2,
        'rate_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id,
            'description' => 'Hotel',
            'amount' => 100,
            'currency' => 'USD',
            'date' => now()->toDateString(),
            'payment_method' => 'card',
            'is_billable' => false,
            'status' => 'approved',
        ]);

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.base_currency_amount', fn ($value): bool => (float) $value === 120.0);
});
