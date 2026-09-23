<?php

namespace Database\Factories;

use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'part_number' => strtoupper(fake()->unique()->bothify('??###-#####')),
            'oem_number' => strtoupper(fake()->bothify('#####-#####')),
            'description' => ucfirst(fake()->words(4, true)),
            'category_id' => PartCategory::factory(),
            'brand_id' => PartBrand::factory(),
            'unit_id' => UnitOfMeasure::factory(),
            'is_oem' => false,
            'is_active' => true,
        ];
    }
}
