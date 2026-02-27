<?php

namespace Tests\Feature\Products;

use App\Enums\ProductSaleStatus;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductGdprTest extends TestCase
{
    use RefreshDatabase;

    public function test_gdpr_export_includes_product_sales(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create(['name' => 'Test Product']);
        $sale = ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'total_price' => 150.00,
            'currency_code' => 'EUR',
            'sold_at' => now(),
        ]);

        $service = new DataExportService();
        $export = $service->exportUserData($user);

        $this->assertArrayHasKey('products', $export);
        $this->assertArrayHasKey('product_sales', $export);

        $this->assertCount(1, $export['products']);
        $this->assertCount(1, $export['product_sales']);
    }

    public function test_product_sales_in_export_have_required_fields(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create(['name' => 'Test Product']);
        ProductSale::factory()->for($product)->for($user)->create([
            'status' => ProductSaleStatus::Confirmed,
            'quantity' => 2,
            'total_price' => 200.00,
            'currency_code' => 'USD',
            'sold_at' => now()->subDays(5),
        ]);

        $service = new DataExportService();
        $export = $service->exportUserData($user);

        $saleExport = $export['product_sales'][0];

        $this->assertArrayHasKey('id', $saleExport);
        $this->assertArrayHasKey('product_name', $saleExport);
        $this->assertArrayHasKey('client_name', $saleExport);
        $this->assertArrayHasKey('quantity', $saleExport);
        $this->assertArrayHasKey('total_price', $saleExport);
        $this->assertArrayHasKey('currency', $saleExport);
        $this->assertArrayHasKey('status', $saleExport);
        $this->assertArrayHasKey('sold_at', $saleExport);
    }

    public function test_gdpr_export_does_not_include_other_users_sales(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $product1 = Product::factory()->for($user1)->create();
        ProductSale::factory()->for($product1)->for($user1)->create();

        $product2 = Product::factory()->for($user2)->create();
        ProductSale::factory()->for($product2)->for($user2)->create();

        $service = new DataExportService();
        $export = $service->exportUserData($user1);

        $this->assertCount(1, $export['products']);
        $this->assertCount(1, $export['product_sales']);
        $this->assertEquals($product1->name, $export['products'][0]['name']);
    }

    public function test_gdpr_export_includes_soft_deleted_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create(['name' => 'Deleted Product']);
        $product->delete();

        $service = new DataExportService();
        $export = $service->exportUserData($user);

        $this->assertCount(1, $export['products']);
        $this->assertEquals('Deleted Product', $export['products'][0]['name']);
    }

    public function test_product_sales_csv_format_in_export(): void
    {
        $user = User::factory()->create();
        $client = \App\Models\Client::factory()->for($user)->create(['name' => 'Test Client']);
        $product = Product::factory()->for($user)->create(['name' => 'Formation Laravel']);

        ProductSale::factory()->for($product)->for($user)->create([
            'client_id' => $client->id,
            'quantity' => 1,
            'total_price' => 500.00,
            'currency_code' => 'EUR',
            'status' => ProductSaleStatus::Confirmed,
            'sold_at' => now(),
        ]);

        $service = new DataExportService();
        $export = $service->exportUserData($user);

        $saleExport = $export['product_sales'][0];

        $this->assertEquals('Formation Laravel', $saleExport['product_name']);
        $this->assertEquals('Test Client', $saleExport['client_name']);
        $this->assertEquals(1, $saleExport['quantity']);
        $this->assertEquals(500.00, $saleExport['total_price']);
        $this->assertEquals('EUR', $saleExport['currency']);
        $this->assertEquals('confirmed', $saleExport['status']);
    }
}
