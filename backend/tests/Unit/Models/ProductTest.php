<?php

namespace Tests\Unit\Models;

use App\Enums\ProductType;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_correct_fillable_attributes(): void
    {
        $product = new Product;

        $this->assertEquals([
            'user_id',
            'name',
            'slug',
            'type',
            'description',
            'short_description',
            'price',
            'price_type',
            'currency_code',
            'vat_rate',
            'duration',
            'duration_unit',
            'sku',
            'tags',
            'is_active',
            'meta',
        ], $product->getFillable());
    }

    public function test_product_has_correct_casts(): void
    {
        $product = new Product;
        $casts = $product->getCasts();

        $this->assertArrayHasKey('price', $casts);
        $this->assertArrayHasKey('vat_rate', $casts);
        $this->assertArrayHasKey('duration', $casts);
        $this->assertArrayHasKey('tags', $casts);
        $this->assertArrayHasKey('meta', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('type', $casts);
        $this->assertArrayHasKey('price_type', $casts);
    }

    public function test_product_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $product->user);
        $this->assertEquals($user->id, $product->user->id);
    }

    public function test_product_has_many_sales(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $sale = ProductSale::factory()->for($product)->for($user)->create();

        $this->assertTrue($product->sales->contains($sale));
        $this->assertInstanceOf(ProductSale::class, $product->sales->first());
    }

    public function test_product_belongs_to_many_campaigns(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $campaign = Campaign::factory()->for($user)->create();

        $product->campaigns()->attach($campaign->id, [
            'generation_model' => 'gemini-2.5-flash',
            'generated_at' => now(),
        ]);

        $this->assertTrue($product->campaigns->contains($campaign));
        $this->assertInstanceOf(Campaign::class, $product->campaigns->first());
        $this->assertEquals('gemini-2.5-flash', $product->campaigns->first()->pivot->generation_model);
    }

    public function test_scope_active_returns_only_active_products(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user)->create(['is_active' => true]);
        Product::factory()->for($user)->create(['is_active' => false]);

        $activeProducts = Product::active()->where('user_id', $user->id)->get();

        $this->assertCount(1, $activeProducts);
        $this->assertTrue($activeProducts->first()->is_active);
    }

    public function test_scope_archived_returns_only_soft_deleted_products(): void
    {
        $user = User::factory()->create();
        $activeProduct = Product::factory()->for($user)->create();
        $deletedProduct = Product::factory()->for($user)->create();
        $deletedProduct->delete();

        $archivedProducts = Product::archived()->where('user_id', $user->id)->get();

        $this->assertCount(1, $archivedProducts);
        $this->assertEquals($deletedProduct->id, $archivedProducts->first()->id);
    }

    public function test_scope_by_type_filters_by_product_type(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user)->create(['type' => ProductType::Service]);
        Product::factory()->for($user)->create(['type' => ProductType::Training]);

        $serviceProducts = Product::byType(ProductType::Service->value)->where('user_id', $user->id)->get();

        $this->assertCount(1, $serviceProducts);
        $this->assertEquals(ProductType::Service->value, $serviceProducts->first()->type->value);
    }

    public function test_scope_by_tag_filters_by_tag(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user)->create(['tags' => ['formation', 'laravel']]);
        Product::factory()->for($user)->create(['tags' => ['consulting']]);

        $laravelProducts = Product::byTag('laravel')->where('user_id', $user->id)->get();

        $this->assertCount(1, $laravelProducts);
        $this->assertContains('laravel', $laravelProducts->first()->tags);
    }

    public function test_to_searchable_array_returns_correct_data(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create([
            'name' => 'Test Product',
            'type' => ProductType::Service,
            'tags' => ['test', 'demo'],
        ]);

        $searchable = $product->toSearchableArray();

        $this->assertArrayHasKey('id', $searchable);
        $this->assertArrayHasKey('user_id', $searchable);
        $this->assertArrayHasKey('name', $searchable);
        $this->assertArrayHasKey('type', $searchable);
        $this->assertArrayHasKey('tags', $searchable);
        $this->assertArrayHasKey('price', $searchable);
        $this->assertArrayHasKey('is_active', $searchable);

        $this->assertEquals('Test Product', $searchable['name']);
        $this->assertEquals(ProductType::Service->value, $searchable['type']);
        $this->assertEquals(['test', 'demo'], $searchable['tags']);
    }

    public function test_product_uses_soft_deletes(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertTrue(Product::withTrashed()->where('id', $product->id)->exists());
    }

    public function test_product_can_be_restored(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();
        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $product->restore();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    }

    public function test_product_has_uuids(): void
    {
        $product = new Product;

        $this->assertTrue(method_exists($product, 'getKeyType'));
        $this->assertEquals('string', $product->getKeyType());
    }
}
