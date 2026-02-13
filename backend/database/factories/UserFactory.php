<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'business_name' => fake()->company(),
            'business_address' => fake()->address(),
            'siret' => fake()->numerify('##############'),
            'ape_code' => fake()->numerify('####'),
            'vat_number' => 'FR' . fake()->numerify('###########'),
            'default_payment_terms' => 30,
        ];
    }
}
