<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->category = ExpenseCategory::factory()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user, 'sanctum');
});

test('profit loss api returns aggregated profit and margin data', function () {
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'paid',
        'total' => 500,
        'currency' => 'EUR',
        'issue_date' => now()->subDays(3)->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $this->category->id,
        'amount' => 150,
        'date' => now()->subDays(2)->toDateString(),
    ]);

    $this->getJson('/api/v1/reports/profit-loss?date_from='.now()->subMonth()->toDateString().'&date_to='.now()->toDateString())
        ->assertStatus(200)
        ->assertJsonPath('data.revenue', 500.0)
        ->assertJsonPath('data.expenses', 150.0)
        ->assertJsonPath('data.profit', 350.0)
        ->assertJsonPath('data.margin', 70.0);
});

test('profit loss api handles loss scenario correctly', function () {
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'paid',
        'total' => 200,
        'currency' => 'EUR',
        'issue_date' => now()->subDays(3)->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $this->category->id,
        'amount' => 300,
        'date' => now()->subDays(2)->toDateString(),
    ]);
    
    $this->getJson('/api/v1/reports/profit-loss')
        ->assertStatus(200)
        ->assertJsonPath('data.revenue', 200.0)
        ->assertJsonPath('data.expenses', 300.0)
        ->assertJsonPath('data.profit', -100.0)
        ->assertJsonPath('data.margin', -50.0);
});

test('profit loss api handles zero revenue scenario', function () {
    Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $this->category->id,
        'amount' => 150,
        'date' => now()->subDays(2)->toDateString(),
    ]);
    
    $this->getJson('/api/v1/reports/profit-loss')
        ->assertStatus(200)
        ->assertJsonPath('data.revenue', 0.0)
        ->assertJsonPath('data.expenses', 150.0)
        ->assertJsonPath('data.profit', -150.0)
        ->assertJsonPath('data.margin', 0.0);
});

test('profit loss api filters by date range', function () {
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'paid',
        'total' => 1000,
        'currency' => 'EUR',
        'issue_date' => now()->subDays(5)->toDateString(),
    ]);
     Invoice::factory()->create([ // Outside date range
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'paid',
        'total' => 5000,
        'currency' => 'EUR',
        'issue_date' => now()->subMonths(2)->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => $this->user->id,
        'amount' => 200,
        'date' => now()->subDays(3)->toDateString(),
    ]);
    
    $from = now()->subMonth()->toDateString();
    $to = now()->toDateString();
    
    $this->getJson("/api/v1/reports/profit-loss?date_from={$from}&date_to={$to}")
        ->assertStatus(200)
        ->assertJsonPath('data.revenue', 1000.0)
        ->assertJsonPath('data.expenses', 200.0)
        ->assertJsonPath('data.profit', 800.0);
});
