<?php

namespace Database\Factories;

use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'model_id' => VehicleModel::factory(),
            'name' => fake()->unique()->bothify('#.# ??-#'),
            'engine_code' => strtoupper(fake()->bothify('#??-???')),
            'fuel_type' => 'diesel',
            'transmission' => 'manual',
            'year_from' => 2016,
        ];
    }
}
