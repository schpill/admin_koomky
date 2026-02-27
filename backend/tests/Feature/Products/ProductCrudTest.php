<?php

use App\Enums\ProductPriceType;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

test('it lists only products belonging to the user', function () {
    Product::factory()->count(3)->create(['user_id' => $this->user->id]);
    Product::factory()->count(2)->create(['user_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('it filters by product type', function () {
    Product::factory()->create(['user_id' => $this->user->id, 'type' => ProductType::Service]);
    Product::factory()->create(['user_id' => $this->user->id, 'type' => ProductType::Training]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/products?type=service');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'service');
});

test('it filters by active status', function () {
    Product::factory()->create(['user_id' => $this->user->id, 'is_active' => true]);
    Product::factory()->create(['user_id' => $this->user->id, 'is_active' => false]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/products?is_active=true');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.is_active', true);
});

test('it creates a product with auto-generated slug', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/products', [
        'name' => 'Formation Laravel',
        'type' => ProductType::Training->value,
        'price' => 1500.00,
        'price_type' => ProductPriceType::Fixed->value,
        'vat_rate' => 20.00,
        'currency_code' => 'EUR',
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Formation Laravel')
        ->assertJsonPath('data.slug', 'formation-laravel');
});

test('it ensures unique slugs for same user', function () {
    Product::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Formation Laravel',
        'slug' => 'formation-laravel',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/products', [
        'name' => 'Formation Laravel',
        'type' => ProductType::Training->value,
        'price' => 2000.00,
        'price_type' => ProductPriceType::Fixed->value,
        'vat_rate' => 20.00,
        'currency_code' => 'EUR',
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.slug', fn ($slug) => str_starts_with($slug, 'formation-laravel-'));
});

test('it allows same slug for different users', function () {
    Product::factory()->create([
        'user_id' => $this->otherUser->id,
        'name' => 'Formation Laravel',
        'slug' => 'formation-laravel',
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/v1/products', [
        'name' => 'Formation Laravel',
        'type' => ProductType::Training->value,
        'price' => 2000.00,
        'price_type' => ProductPriceType::Fixed->value,
        'vat_rate' => 20.00,
        'currency_code' => 'EUR',
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.slug', 'formation-laravel');
});

test('it returns product details', function () {
    $product = Product::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->getJson("/api/v1/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $product->id);
});

test('it prevents viewing other users product', function () {
    $product = Product::factory()->create(['user_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)->getJson("/api/v1/products/{$product->id}");

    $response->assertStatus(403);
});

test('it updates a product', function () {
    $product = Product::factory()->create(['user_id' => $this->user->id, 'name' => 'Old Name']);

    $response = $this->actingAs($this->user)->patchJson("/api/v1/products/{$product->id}", [
        'name' => 'New Name',
        'price' => 999.99,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.price', '999.99');
});

test('it soft deletes a product', function () {
    $product = Product::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/products/{$product->id}");

    $response->assertStatus(204);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => now()]);
});

test('it restores a soft deleted product', function () {
    $product = Product::factory()->create(['user_id' => $this->user->id]);
    $product->delete();

    $response = $this->actingAs($this->user)->postJson("/api/v1/products/{$product->id}/restore");

    $response->assertStatus(200);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
});

test('it prevents updating other users product', function () {
    $product = Product::factory()->create(['user_id' => $this->otherUser->id]);

    $response = $this->actingAs($this->user)->patchJson("/api/v1/products/{$product->id}", [
        'name' => 'New Name',
    ]);

    $response->assertStatus(403);
});

test('it validates required fields on create', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/products', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'type', 'price', 'price_type', 'vat_rate', 'currency_code']);
});

test('it validates price is numeric and positive', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/products', [
        'name' => 'Test Product',
        'type' => ProductType::Service->value,
        'price' => -100,
        'price_type' => ProductPriceType::Fixed->value,
        'vat_rate' => 20,
        'currency_code' => 'EUR',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
});
