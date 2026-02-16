<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\LineItem>
 */
class LineItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->randomFloat(2, 50, 500);

        return [
            'documentable_type' => Invoice::class,
            'documentable_id' => Invoice::factory(),
            'description' => $this->faker->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'vat_rate' => $this->faker->randomElement([0, 5.5, 10, 20]),
            'total' => round($quantity * $unitPrice, 2),
            'sort_order' => 0,
        ];
    }
}
