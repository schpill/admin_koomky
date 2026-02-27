<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductPriceType;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        $types = array_map(fn (ProductType $type) => $type->value, ProductType::cases());
        $priceTypes = array_map(fn (ProductPriceType $type) => $type->value, ProductPriceType::cases());

        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.$this->faker->unique()->randomNumber(5),
            'type' => $this->faker->randomElement($types),
            'description' => $this->faker->paragraphs(2, true),
            'short_description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 50, 5000),
            'price_type' => $this->faker->randomElement($priceTypes),
            'currency_code' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'vat_rate' => $this->faker->randomElement([0, 5.5, 10, 20]),
            'duration' => $this->faker->optional(0.7)->numberBetween(1, 30),
            'duration_unit' => $this->faker->optional(0.7)->randomElement(['hours', 'days', 'weeks', 'months']),
            'sku' => $this->faker->optional()->regexify('[A-Z]{3}-[0-9]{4}'),
            'tags' => $this->faker->optional()->randomElements(
                ['formation', 'consulting', 'design', 'development', 'marketing', 'support'],
                $this->faker->numberBetween(1, 3)
            ),
            'is_active' => true,
            'meta' => null,
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is a training.
     */
    public function training(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ProductType::Training->value,
            'duration' => $this->faker->numberBetween(1, 10),
            'duration_unit' => 'days',
        ]);
    }

    /**
     * Indicate that the product is a subscription.
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ProductType::Subscription->value,
            'price_type' => ProductPriceType::Fixed->value,
        ]);
    }

    /**
     * Indicate that the product is a service.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ProductType::Service->value,
            'price_type' => ProductPriceType::Hourly->value,
        ]);
    }
}
