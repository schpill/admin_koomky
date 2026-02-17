<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => 'EUR', 'decimal_places' => 2],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => 'USD', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'Pound Sterling', 'symbol' => 'GBP', 'decimal_places' => 2],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimal_places' => 2],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CAD', 'decimal_places' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => 'JPY', 'decimal_places' => 0],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'AUD', 'decimal_places' => 2],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'SEK', 'decimal_places' => 2],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'NOK', 'decimal_places' => 2],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'DKK', 'decimal_places' => 2],
            ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'PLN', 'decimal_places' => 2],
            ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'CZK', 'decimal_places' => 2],
            ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'HUF', 'decimal_places' => 2],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'BRL', 'decimal_places' => 2],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => 'CNY', 'decimal_places' => 2],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                [...$currency, 'is_active' => true]
            );
        }
    }
}
