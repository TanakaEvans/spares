<?php

namespace Database\Factories;

use App\Models\VehicleMake;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'make_id' => VehicleMake::factory(),
            'name' => ucfirst(fake()->unique()->word()),
            'body_type' => 'pickup',
            'year_from' => 2010,
            'is_active' => true,
        ];
    }
}
