<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_base' => true, 'is_active' => true],
            ['code' => 'ZWG', 'name' => 'Zimbabwe Gold', 'symbol' => 'ZiG', 'decimal_places' => 2, 'is_base' => false, 'is_active' => true],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'decimal_places' => 2, 'is_base' => false, 'is_active' => true],
            ['code' => 'BWP', 'name' => 'Botswana Pula', 'symbol' => 'P', 'decimal_places' => 2, 'is_base' => false, 'is_active' => false],
            ['code' => 'ZMW', 'name' => 'Zambian Kwacha', 'symbol' => 'K', 'decimal_places' => 2, 'is_base' => false, 'is_active' => false],
            ['code' => 'MZN', 'name' => 'Mozambican Metical', 'symbol' => 'MT', 'decimal_places' => 2, 'is_base' => false, 'is_active' => false],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'is_base' => false, 'is_active' => false],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2, 'is_base' => false, 'is_active' => false],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_places' => 2, 'is_base' => false, 'is_active' => false],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0, 'is_base' => false, 'is_active' => false],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(['code' => $currency['code']], $currency);
        }
    }
}
