<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('can get monthly vat declaration', function () {
    $response = $this->getJson('/api/v1/accounting/vat?period_type=monthly&year=2024');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'year',
                'period_type',
                'periods',
                'totals',
            ],
        ]);
});

test('can get quarterly vat declaration', function () {
    $response = $this->getJson('/api/v1/accounting/vat?period_type=quarterly&year=2024');

    $response->assertStatus(200)
        ->assertJsonPath('data.period_type', 'quarterly');
});

test('can export vat as csv', function () {
    $response = $this->getJson('/api/v1/accounting/vat/export?period_type=monthly&year=2024');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('vat includes correct totals per period', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $category = \App\Models\ExpenseCategory::factory()->create(['user_id' => $this->user->id]);

    // Invoice with VAT
    $invoice = \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'tax_amount' => 40.00,
    ]);

    // Create line item directly to avoid factory issues
    \Illuminate\Support\Facades\DB::table('line_items')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'documentable_type' => \App\Models\Invoice::class,
        'documentable_id' => $invoice->id,
        'description' => 'Test item',
        'quantity' => 1,
        'unit_price' => 200.00,
        'vat_rate' => 20.00,
        'total' => 200.00,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Expense with VAT
    \App\Models\Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $category->id,
        'status' => 'approved',
        'date' => '2024-06-10',
        'tax_amount' => 10.00,
    ]);

    $response = $this->getJson('/api/v1/accounting/vat?period_type=monthly&year=2024');

    $response->assertStatus(200);

    $periods = $response->json('data.periods');
    $junePeriod = collect($periods)->firstWhere('month', 6);

    expect($junePeriod['total_collected'])->toBe(40.00)
        ->and($junePeriod['total_deductible'])->toBe(10.00)
        ->and($junePeriod['net_due'])->toBe(30.00);
});
