<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\Searchable;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_is_indexed_in_meilisearch(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create([
            'name' => 'Formation Laravel Avancé',
            'description' => 'Une formation complète sur Laravel',
        ]);

        $searchableArray = $product->toSearchableArray();

        $this->assertArrayHasKey('id', $searchableArray);
        $this->assertArrayHasKey('user_id', $searchableArray);
        $this->assertArrayHasKey('name', $searchableArray);
        $this->assertArrayHasKey('description', $searchableArray);
        $this->assertArrayHasKey('tags', $searchableArray);
        $this->assertEquals('Formation Laravel Avancé', $searchableArray['name']);
    }

    public function test_product_has_searchable_trait(): void
    {
        $product = new Product();

        $this->assertTrue(in_array(Searchable::class, class_uses_recursive($product)));
    }

    public function test_product_searchable_configuration_is_correct(): void
    {
        $product = new Product();

        $config = $product->searchableConfiguration();

        $this->assertArrayHasKey('searchableAttributes', $config);
        $this->assertArrayHasKey('filterableAttributes', $config);
        $this->assertArrayHasKey('sortableAttributes', $config);

        $this->assertEquals(['name', 'description', 'short_description', 'tags'], $config['searchableAttributes']);
        $this->assertEquals(['user_id', 'type', 'is_active'], $config['filterableAttributes']);
        $this->assertEquals(['created_at', 'name', 'price'], $config['sortableAttributes']);
    }

    public function test_filterable_attributes_method_returns_correct_values(): void
    {
        $product = new Product();

        $this->assertEquals(['user_id', 'type', 'is_active'], $product->getFilterableAttributes());
    }

    public function test_sortable_attributes_method_returns_correct_values(): void
    {
        $product = new Product();

        $this->assertEquals(['created_at', 'name', 'price'], $product->getSortableAttributes());
    }

    public function test_api_search_filters_by_type(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user)->create(['type' => \App\Enums\ProductType::Service]);
        Product::factory()->for($user)->create(['type' => \App\Enums\ProductType::Training]);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/products?type=service');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('service', $data[0]['type']);
    }

    public function test_api_search_filters_by_active_status(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user)->create(['is_active' => true, 'name' => 'Active Product']);
        Product::factory()->for($user)->create(['is_active' => false, 'name' => 'Inactive Product']);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/products?is_active=true');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Active Product', $data[0]['name']);
    }

    public function test_api_search_filters_by_inactive_status(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user)->create(['is_active' => true, 'name' => 'Active Product']);
        Product::factory()->for($user)->create(['is_active' => false, 'name' => 'Inactive Product']);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/products?is_active=false');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Inactive Product', $data[0]['name']);
    }

    public function test_api_defaults_to_active_products_only(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user)->create(['is_active' => true]);
        Product::factory()->for($user)->create(['is_active' => false]);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_active']);
    }

    public function test_search_excludes_other_users_products(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Product::factory()->for($user1)->create(['name' => 'User1 Product']);
        Product::factory()->for($user2)->create(['name' => 'User2 Product']);

        $this->actingAs($user1);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('User1 Product', $data[0]['name']);
    }
}
