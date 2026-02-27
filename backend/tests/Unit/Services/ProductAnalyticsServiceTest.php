<?php

namespace Tests\Unit\Services;

use App\Enums\ProductSaleStatus;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use App\Services\ProductAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductAnalyticsService;
    }

    public function test_product_stats_returns_correct_shape(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        $stats = $this->service->productStats($product);

        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('total_sales', $stats);
        $this->assertArrayHasKey('pending_sales', $stats);
        $this->assertArrayHasKey('avg_order_value', $stats);
        $this->assertArrayHasKey('conversion_rate', $stats);
        $this->assertArrayHasKey('monthly_breakdown', $stats);
        $this->assertIsArray($stats['monthly_breakdown']);
    }

    public function test_product_stats_calculates_total_revenue_from_confirmed_sales(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        // Create confirmed sales
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 100.00,
            'sold_at' => now(),
        ]);
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 200.00,
            'sold_at' => now(),
        ]);

        // Create pending sale (should not count toward revenue)
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Pending,
            'total_price' => 50.00,
            'sold_at' => now(),
        ]);

        $stats = $this->service->productStats($product);

        $this->assertEquals(300.00, $stats['total_revenue']);
        $this->assertEquals(2, $stats['total_sales']);
        $this->assertEquals(1, $stats['pending_sales']);
    }

    public function test_product_stats_calculates_average_order_value(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 100.00,
            'sold_at' => now(),
        ]);
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 200.00,
            'sold_at' => now(),
        ]);

        $stats = $this->service->productStats($product);

        $this->assertEquals(150.00, $stats['avg_order_value']);
    }

    public function test_product_stats_returns_zero_for_empty_sales(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        $stats = $this->service->productStats($product);

        $this->assertEquals(0, $stats['total_revenue']);
        $this->assertEquals(0, $stats['total_sales']);
        $this->assertEquals(0, $stats['avg_order_value']);
    }

    public function test_product_stats_respects_date_range(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        // Sale within range
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 100.00,
            'sold_at' => now()->subDays(5),
        ]);

        // Sale outside range
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 200.00,
            'sold_at' => now()->subDays(20),
        ]);

        $from = now()->subDays(10);
        $to = now();

        $stats = $this->service->productStats($product, $from, $to);

        $this->assertEquals(100.00, $stats['total_revenue']);
        $this->assertEquals(1, $stats['total_sales']);
    }

    public function test_product_stats_monthly_breakdown_has_12_months(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        $stats = $this->service->productStats($product);

        $this->assertCount(12, $stats['monthly_breakdown']);

        foreach ($stats['monthly_breakdown'] as $month) {
            $this->assertArrayHasKey('month', $month);
            $this->assertArrayHasKey('month_iso', $month);
            $this->assertArrayHasKey('revenue', $month);
            $this->assertArrayHasKey('sales_count', $month);
        }
    }

    public function test_global_stats_returns_correct_shape(): void
    {
        $user = User::factory()->create();

        $stats = $this->service->globalStats($user);

        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('total_sales', $stats);
        $this->assertArrayHasKey('top_products', $stats);
        $this->assertIsArray($stats['top_products']);
    }

    public function test_global_stats_aggregates_all_user_sales(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->for($user)->create();
        $product2 = Product::factory()->for($user)->create();

        ProductSale::factory()->for($product1)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 100.00,
            'sold_at' => now(),
        ]);
        ProductSale::factory()->for($product2)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 200.00,
            'sold_at' => now(),
        ]);

        $stats = $this->service->globalStats($user);

        $this->assertEquals(300.00, $stats['total_revenue']);
        $this->assertEquals(2, $stats['total_sales']);
    }

    public function test_global_stats_does_not_include_other_users_sales(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->for($user2)->create();

        ProductSale::factory()->for($product)->for($user2)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 100.00,
            'sold_at' => now(),
        ]);

        $stats = $this->service->globalStats($user1);

        $this->assertEquals(0, $stats['total_revenue']);
        $this->assertEquals(0, $stats['total_sales']);
    }

    public function test_top_products_returns_products_sorted_by_revenue(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->for($user)->create(['name' => 'Low Revenue']);
        $product2 = Product::factory()->for($user)->create(['name' => 'High Revenue']);
        $product3 = Product::factory()->for($user)->create(['name' => 'Medium Revenue']);

        // High revenue
        ProductSale::factory()->for($product2)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 500.00,
            'sold_at' => now(),
        ]);

        // Medium revenue
        ProductSale::factory()->for($product3)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 300.00,
            'sold_at' => now(),
        ]);

        // Low revenue
        ProductSale::factory()->for($product1)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 100.00,
            'sold_at' => now(),
        ]);

        $topProducts = $this->service->topProducts($user, 5);

        $this->assertCount(3, $topProducts);
        $this->assertEquals('High Revenue', $topProducts->first()->name);
        $this->assertEquals('Low Revenue', $topProducts->last()->name);
    }

    public function test_top_products_respects_limit(): void
    {
        $user = User::factory()->create();

        // Create 5 products with sales
        for ($i = 0; $i < 5; $i++) {
            $product = Product::factory()->for($user)->create();
            ProductSale::factory()->for($product)->for($user)->create([
                'status' => ProductSaleStatus::Confirmed,
                'total_price' => 100.00,
                'sold_at' => now(),
            ]);
        }

        $topProducts = $this->service->topProducts($user, 3);

        $this->assertCount(3, $topProducts);
    }

    public function test_top_products_defaults_to_current_month(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        // Sale this month
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 100.00,
            'sold_at' => now(),
        ]);

        // Sale last month
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 200.00,
            'sold_at' => now()->subMonth(),
        ]);

        $topProducts = $this->service->topProducts($user);

        // Should only count this month's sale
        $this->assertEquals(1, $topProducts->first()->sales_count);
        $this->assertEquals(100.00, $topProducts->first()->sales_revenue);
    }
}
