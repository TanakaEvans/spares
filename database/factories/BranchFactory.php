<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->city().' Branch',
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'is_main_branch' => false,
            'status' => 'active',
        ];
    }
}
