<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->unique()->randomElement([
                'Travel',
                'Software',
                'Hardware',
                'Office',
                'Meals',
            ]),
            'color' => $this->faker->hexColor(),
            'icon' => $this->faker->randomElement(['briefcase', 'plane', 'monitor', 'coffee', 'wallet']),
            'is_default' => false,
        ];
    }
}
