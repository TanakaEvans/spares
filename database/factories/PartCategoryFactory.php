<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'code' => strtoupper(fake()->unique()->bothify('???-##')),
            'is_active' => true,
        ];
    }
}
