<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->currencyCode().' Currency',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_base' => false,
            'is_active' => true,
        ];
    }

    public function base(): static
    {
        return $this->state(fn () => ['is_base' => true]);
    }
}
