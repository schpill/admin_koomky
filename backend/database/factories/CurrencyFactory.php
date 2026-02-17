<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alphabet = range('A', 'Z');
        $code = $alphabet[array_rand($alphabet)]
            .$alphabet[array_rand($alphabet)]
            .$alphabet[array_rand($alphabet)];

        return [
            'code' => $code,
            'name' => $code.' currency',
            'symbol' => $code,
            'decimal_places' => $code === 'JPY' ? 0 : 2,
            'is_active' => true,
        ];
    }
}
