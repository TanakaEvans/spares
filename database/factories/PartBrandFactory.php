<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartBrandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'is_oem_brand' => false,
            'is_active' => true,
        ];
    }
}
