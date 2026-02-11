<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake('firstName'),
            'email' => fake('safeEmail'),
            'password' => '$2y$10$92XUNwoOL2Q7pZM/ha5x/PH876K1YqxRz5xw2//e+e+BvX17ca9f+e', // bcrypt
            'business_name' => fake('company'),
            'business_address' => fake('address'),
            'siret' => fake('numerify')('##########'),
            'ape_code' => fake('bothify')('####'),
            'vat_number' => fake('vatNumber', 'FR'),
            'default_payment_terms' => 30,
        ];
    }
}
