<?php

use App\Models\ExchangeRate;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'base_currency' => 'EUR',
    ]);
    $this->category = ExpenseCategory::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $this->actingAs($this->user, 'sanctum');
});

test('expense creation computes base currency amount using exchange rates', function () {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'target_currency' => 'EUR',
        'rate' => 1.2,
        'rate_date' => now()->toDateString(),
    ]);

    $this->postJson('/api/v1/expenses', [
        'expense_category_id' => $this->category->id,
        'description' => 'Hotel',
        'amount' => 100,
        'currency' => 'USD',
        'date' => now()->toDateString(),
        'payment_method' => 'card',
        'status' => 'approved',
    ])
        ->assertStatus(201)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.amount', fn ($value) => (float) $value === 100.0)
        ->assertJsonPath('data.base_currency_amount', fn ($value) => (float) $value === 120.0);
});

test('expense creation fails if exchange rate is missing', function () {
    // Note: The actual error message from the service might differ slightly.
    // This test ensures a 422 is returned when a rate is not found.
    $this->postJson('/api/v1/expenses', [
        'expense_category_id' => $this->category->id,
        'description' => 'Software from Japan',
        'amount' => 15000,
        'currency' => 'JPY',
        'date' => now()->toDateString(),
        'payment_method' => 'card',
        'status' => 'approved',
    ])
        ->assertStatus(422);
});

test('expense in base currency has same amount and base_currency_amount', function () {
    $this->postJson('/api/v1/expenses', [
        'expense_category_id' => $this->category->id,
        'description' => 'Local supply',
        'amount' => 50,
        'currency' => 'EUR',
        'date' => now()->toDateString(),
        'payment_method' => 'card',
        'status' => 'approved',
    ])
        ->assertStatus(201)
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.amount', fn ($value) => (float) $value === 50.0)
        ->assertJsonPath('data.base_currency_amount', fn ($value) => (float) $value === 50.0);
});
