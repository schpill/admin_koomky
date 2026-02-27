<?php

use App\Enums\ProductSaleStatus;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->product = Product::factory()->create(['user_id' => $this->user->id]);
});

test('it returns product analytics', function () {
    // Create confirmed sales
    ProductSale::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'client_id' => $this->client->id,
        'status' => ProductSaleStatus::Confirmed->value,
        'total_price' => 1000.00,
        'sold_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/products/{$this->product->id}/analytics"
    );

    $response->assertStatus(200)
        ->assertJsonPath('data.total_sales', 3)
        ->assertJsonPath('data.total_revenue', fn ($value) => (float) $value === 3000.0)
        ->assertJsonStructure([
            'data' => [
                'total_revenue',
                'total_sales',
                'pending_sales',
                'avg_order_value',
                'conversion_rate',
                'monthly_breakdown',
            ],
        ]);
});

test('it filters analytics by date range', function () {
    // Sale in current month
    ProductSale::factory()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'client_id' => $this->client->id,
        'status' => ProductSaleStatus::Confirmed->value,
        'total_price' => 1000.00,
        'sold_at' => now(),
    ]);

    // Sale in previous month
    ProductSale::factory()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'client_id' => $this->client->id,
        'status' => ProductSaleStatus::Confirmed->value,
        'total_price' => 500.00,
        'sold_at' => now()->subMonth(),
    ]);

    $from = now()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/products/{$this->product->id}/analytics?from={$from}&to={$to}"
    );

    $response->assertStatus(200)
        ->assertJsonPath('data.total_sales', 1)
        ->assertJsonPath('data.total_revenue', fn ($value) => (float) $value === 1000.0);
});

test('it returns global analytics', function () {
    $product2 = Product::factory()->create(['user_id' => $this->user->id]);

    // Sales for product 1
    ProductSale::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'client_id' => $this->client->id,
        'status' => ProductSaleStatus::Confirmed->value,
        'total_price' => 1000.00,
        'sold_at' => now(),
    ]);

    // Sales for product 2
    ProductSale::factory()->create([
        'user_id' => $this->user->id,
        'product_id' => $product2->id,
        'client_id' => $this->client->id,
        'status' => ProductSaleStatus::Confirmed->value,
        'total_price' => 500.00,
        'sold_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/products/analytics');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_sales', 3)
        ->assertJsonPath('data.total_revenue', fn ($value) => (float) $value === 2500.0)
        ->assertJsonCount(2, 'data.top_products');
});

test('it prevents viewing analytics of other users product', function () {
    $otherUser = User::factory()->create();
    $otherProduct = Product::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/products/{$otherProduct->id}/analytics"
    );

    $response->assertStatus(403);
});
