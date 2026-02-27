<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductSale>
 */
class ProductSaleFactory extends Factory
{
    protected $model = ProductSale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory();
        $user = User::factory();

        $unitPrice = $this->faker->randomFloat(2, 50, 1000);
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $totalPrice = round($unitPrice * $quantity, 2);

        return [
            'id' => (string) Str::uuid(),
            'product_id' => $product,
            'user_id' => $user,
            'client_id' => Client::factory()->for($user, 'user'),
            'invoice_id' => null,
            'quote_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'currency_code' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'status' => \App\Enums\ProductSaleStatus::Pending->value,
            'sold_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the sale is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => \App\Enums\ProductSaleStatus::Confirmed->value,
            'invoice_id' => Invoice::factory(),
        ]);
    }

    /**
     * Indicate that the sale is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => \App\Enums\ProductSaleStatus::Pending->value,
            'quote_id' => Quote::factory(),
        ]);
    }

    /**
     * Indicate that the sale is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => \App\Enums\ProductSaleStatus::Cancelled->value,
        ]);
    }

    /**
     * Indicate that the sale is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => \App\Enums\ProductSaleStatus::Delivered->value,
            'invoice_id' => Invoice::factory(),
        ]);
    }

    /**
     * Indicate that the sale is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => \App\Enums\ProductSaleStatus::Refunded->value,
            'invoice_id' => Invoice::factory(),
        ]);
    }
}
